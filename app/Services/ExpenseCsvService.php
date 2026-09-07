<?php

namespace App\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseCsvService
{
    public const HEADERS = ['日付', '店舗', 'カテゴリ', '品目', '単価', '数量'];

    public function parse(UploadedFile $file, string $encoding, bool $allowUnknownCategories = false): array
    {
        $text = $file->getContent();
        if (! mb_check_encoding($text, $encoding)) {
            throw ValidationException::withMessages(['file' => '文字コードが一致しません。選択した文字コードを確認してください。']);
        }
        $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        if (str_contains($text, "\0")) {
            throw ValidationException::withMessages(['file' => 'CSV形式のテキストファイルを選択してください。']);
        }
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, str_replace(["\r\n", "\r"], "\n", $text));
        rewind($stream);
        $categories = DB::table('expense_categories')->pluck('id', 'name')->all();
        $records = [];
        $errors = [];
        $line = 1;
        try {
            $header = fgetcsv($stream, null, ',', '"', '');
            if ($header === false || array_map('trim', $header) !== self::HEADERS) {
                throw ValidationException::withMessages(['file' => '先頭行は「日付,店舗,カテゴリ,品目,単価,数量」の順にしてください。テンプレートを利用できます。']);
            }
            while (! feof($stream)) {
                $offset = ftell($stream);
                $row = fgetcsv($stream, null, ',', '"', '');
                $end = ftell($stream);
                if ($row === false) {
                    break;
                }
                fseek($stream, $offset);
                $raw = fread($stream, $end - $offset);
                $rowLine = $line + 1;
                $line += substr_count($raw, "\n");
                if ($row === [null]) {
                    continue;
                }
                if (count($records) + count($errors) >= 1000) {
                    throw ValidationException::withMessages(['file' => '取り込める支出は1ファイル1,000件までです。']);
                }
                if (count($row) !== 6 || ! $this->validCsvRecord($raw)) {
                    $errors[] = ['line' => $rowLine, 'message' => '6列のCSV形式になっていません。カンマや引用符を確認してください。'];

                    continue;
                }
                [$date, $store, $category, $name, $price, $quantity] = array_map('trim', $row);
                $category = preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $category);
                $category = $categories[$category] ?? $category;
                $unknownCategory = ! in_array($category, array_values($categories), true);
                $validator = Validator::make(compact('date', 'store', 'category', 'name', 'price', 'quantity'), [
                    'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
                    'store' => ['required', 'string', 'max:255'],
                    'category' => ['required', 'string', 'max:50', 'not_regex:/[\x00-\x1F\x7F]/', function ($attribute, $value, $fail) use ($unknownCategory, $allowUnknownCategories) {
                        if ($unknownCategory && ! $allowUnknownCategories) {
                            $fail('未登録カテゴリです。');
                        }
                    }],
                    'name' => ['required', 'string', 'max:255'],
                    'price' => ['required', 'regex:/^[0-9]+$/', 'numeric', 'between:0,100000000'],
                    'quantity' => ['required', 'regex:/^[0-9]+$/', 'numeric', 'between:1,10000'],
                ]);
                if ($validator->fails()) {
                    $labels = ['date' => '日付（YYYY-MM-DD）', 'store' => '店舗（255文字以内）', 'category' => 'カテゴリ', 'name' => '品目（255文字以内）', 'price' => '単価（0〜100,000,000の整数）', 'quantity' => '数量（1〜10,000の整数）'];
                    $invalid = array_map(fn ($key) => $labels[$key], $validator->errors()->keys());
                    $errors[] = ['line' => $rowLine, 'message' => implode('・', $invalid).'を確認してください。'];

                    continue;
                }
                $records[] = ['line' => $rowLine, 'date' => $date, 'store' => $store, 'category' => $category, 'items' => [['name' => $name, 'unitPrice' => (int) $price, 'quantity' => (int) $quantity]]];
                if ($unknownCategory) {
                    $records[array_key_last($records)]['categoryName'] = $category;
                }
            }
        } finally {
            fclose($stream);
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['rows' => array_map(fn ($error) => $error['line'].'行目: '.$error['message'], array_slice($errors, 0, 50))]);
        }
        if ($records === []) {
            throw ValidationException::withMessages(['file' => '取り込める支出がありません。ヘッダーの次の行から入力してください。']);
        }

        return $records;
    }

    // RFC-style quoting: quoted commas/newlines and doubled quotes are allowed.
    private function validCsvRecord(string $raw): bool
    {
        return preg_match('/\A(?:"(?:[^"]|"")*"|[^",\r\n]*)(?:,(?:"(?:[^"]|"")*"|[^",\r\n]*))*\n?\z/sD', $raw) === 1;
    }

    private function fingerprint(array $records): string
    {
        $data = array_map(function ($row) {
            unset($row['line'], $row['categoryName']);

            return $row;
        }, $records);

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function preview(array $records): array
    {
        return [
            'count' => count($records),
            'total' => array_sum(array_map(fn ($r) => $r['items'][0]['unitPrice'] * $r['items'][0]['quantity'], $records)),
            'rows' => array_slice($records, 0, 20),
            'unknownCategories' => array_values(array_unique(array_column($records, 'categoryName'))),
            'alreadyImported' => DB::table('expense_imports')->where('fingerprint', $this->fingerprint($records))->exists(),
        ];
    }

    public function import(array $records, ExpenseService $expenses, array $approvedCategories = []): array
    {
        $fingerprint = null;
        try {
            return DB::transaction(function () use (&$fingerprint, $records, $expenses, $approvedCategories) {
                $names = array_values(array_unique(array_column($records, 'categoryName')));
                sort($names, SORT_STRING);
                $resolved = [];
                foreach ($names as $name) {
                    $category = DB::table('expense_categories')->where('name', $name)->first();
                    if (! $category) {
                        if (! in_array($name, $approvedCategories, true)) {
                            throw ValidationException::withMessages(['categories' => '未登録カテゴリ「'.$name.'」の追加が承認されていません。内容を確認してください。']);
                        }
                        // The unique name constraint also handles concurrent category creation.
                        DB::table('expense_categories')->insertOrIgnore(['id' => (string) Str::ulid(), 'name' => $name]);
                        $category = DB::table('expense_categories')->where('name', $name)->lockForUpdate()->firstOrFail();
                    }
                    $resolved[$name] = $category->id;
                }
                foreach ($records as &$record) {
                    if (isset($record['categoryName'])) {
                        $record['category'] = $resolved[$record['categoryName']];
                        unset($record['categoryName']);
                    }
                }
                unset($record);
                $fingerprint = $this->fingerprint($records);
                if (DB::table('expense_imports')->where('fingerprint', $fingerprint)->exists()) {
                    return ['importedCount' => 0, 'alreadyImported' => true];
                }
                DB::table('expense_imports')->insert(['fingerprint' => $fingerprint, 'expense_count' => count($records), 'created_at' => now()]);
                foreach ($records as $record) {
                    $expenses->create($record);
                }

                return ['importedCount' => count($records), 'alreadyImported' => false];
            }, 3);
        } catch (UniqueConstraintViolationException $error) {
            if ($fingerprint === null || ! DB::table('expense_imports')->where('fingerprint', $fingerprint)->exists()) {
                throw $error;
            }

            return ['importedCount' => 0, 'alreadyImported' => true];
        }
    }
}
