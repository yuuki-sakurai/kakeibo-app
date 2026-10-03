<?php

namespace App\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StatementImportService
{
    public const HEADER = ['利用日', '利用場所', 'カテゴリ', '金額', '支払予定月'];

    public function import(UploadedFile $file, int $cardId): array
    {
        $userId = ExpenseAccess::userId();
        $bytes = $file->get();
        $fingerprint = hash('sha256', $bytes);
        $existing = fn () => DB::table('statement_imports')->where('user_id', $userId)->where('credit_card_id', $cardId)->where('fingerprint', $fingerprint)->first();
        if ($found = $existing()) {
            return ['import' => $found, 'alreadyImported' => true];
        }
        $encoding = mb_detect_encoding($bytes, ['UTF-8', 'SJIS-win'], true);
        if ($encoding === false) {
            $this->invalid('UTF-8 または Shift_JIS のCSVを選択してください。');
        }
        $text = preg_replace('/^\xEF\xBB\xBF/', '', mb_convert_encoding($bytes, 'UTF-8', $encoding));
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $text);
        rewind($stream);
        $rows = [];
        $categories = ExpenseAccess::categories()->pluck('id', 'name');
        try {
            if (fgetcsv($stream, null, ',', '"', '') !== self::HEADER) {
                $this->invalid('共通テンプレートのヘッダー（利用日,利用場所,カテゴリ,金額,支払予定月）を使用してください。');
            }
            $line = 1;
            while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $line++;
                if ($row === [null]) {
                    continue;
                }
                if (count($rows) >= 5000 || count($row) !== 5) {
                    $this->invalid("{$line}行目: 列数が不正、または上限5,000件を超えています。");
                }
                [$date, $merchant, $category, $amount, $paymentMonth] = array_map('trim', $row);
                if (! $this->validDate($date, 'Y-m-d') || ! $this->validDate($paymentMonth, 'Y-m') || (int) substr($date, 0, 4) < 1900 || (int) substr($paymentMonth, 0, 4) < 1900 || (int) substr($date, 0, 4) > 9998 || (int) substr($paymentMonth, 0, 4) > 9998) {
                    $this->invalid("{$line}行目: 利用日はYYYY-MM-DD、支払予定月はYYYY-MMで入力してください。");
                }
                if ($merchant === '' || mb_strlen($merchant) > 200 || ! preg_match('/^-?[0-9]{1,9}$/D', $amount)) {
                    $this->invalid("{$line}行目: 利用場所は1〜200文字、金額は9桁以内の整数円で入力してください。");
                }
                if ($category !== '' && ! $categories->has($category)) {
                    $this->invalid("{$line}行目: カテゴリ「{$category}」は未登録です。家計簿で登録するか、空欄にしてください。");
                }
                $rows[] = ['user_id' => $userId, 'credit_card_id' => $cardId, 'date' => $date, 'merchant' => $merchant, 'category_id' => $category === '' ? null : $categories[$category], 'amount' => (int) $amount, 'payment_month' => $paymentMonth.'-01'];
            }
        } finally {
            fclose($stream);
        }
        if ($rows === []) {
            $this->invalid('CSVに明細がありません。');
        }
        try {
            $import = DB::transaction(function () use ($rows, $userId, $cardId, $fingerprint, $file) {
                $now = now();
                $id = DB::table('statement_imports')->insertGetId(['user_id' => $userId, 'credit_card_id' => $cardId, 'file_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'fingerprint' => $fingerprint, 'imported_count' => count($rows), 'created_at' => $now, 'updated_at' => $now]);
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table('credit_card_transactions')->insert(array_map(fn ($row) => $row + ['statement_import_id' => $id, 'created_at' => $now, 'updated_at' => $now], $chunk));
                }

                return DB::table('statement_imports')->where('id', $id)->first();
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($found = $existing()) {
                return ['import' => $found, 'alreadyImported' => true];
            }
            throw $e;
        }

        return ['import' => $import, 'alreadyImported' => false];
    }

    private function validDate(string $value, string $format): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);

        return $date !== false && $date->format($format) === $value;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
