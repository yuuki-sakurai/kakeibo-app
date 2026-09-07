<?php

namespace Tests\Feature;

use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExpenseCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private const HEADER = "日付,店舗,カテゴリ,品目,単価,数量\n";

    private function upload(string $csv, string $action = 'preview', string $encoding = 'UTF-8', array $approved = [])
    {
        return $this->post('/api/v1/expense-imports'.($action === 'preview' ? '/preview' : ''), [
            'file' => UploadedFile::fake()->createWithContent('expenses.csv', $csv),
            'encoding' => $encoding,
            'approved_categories' => $approved,
        ], ['Accept' => 'application/json']);
    }

    public function test_preview_supports_bom_japanese_categories_quotes_and_blank_lines_without_saving(): void
    {
        $csv = "\xEF\xBB\xBF".self::HEADER."2026-09-01,\"お店,支店\",食費,\"りんご\"\"特売\"\"\",150,2\n\n2026-09-02,カフェ,dining,コーヒー,450,1\n";
        $this->upload($csv)->assertOk()->assertJsonPath('count', 2)->assertJsonPath('total', 750)
            ->assertJsonPath('rows.0.store', 'お店,支店')->assertJsonPath('rows.0.category', 'food')
            ->assertJsonPath('rows.0.items.0.name', 'りんご"特売"')
            ->assertJsonPath('rows.1.line', 4)->assertJsonPath('alreadyImported', false);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_imports', 0);
    }

    public function test_import_saves_all_rows_and_retry_does_not_duplicate_even_after_encoding_change(): void
    {
        $csv = self::HEADER."2026-09-01,スーパー,食費,りんご,150,2\n2026-09-02,カフェ,外食,コーヒー,450,1\n";
        $this->upload($csv, 'import')->assertCreated()->assertJsonPath('importedCount', 2);
        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseCount('expense_items', 2);
        $this->assertDatabaseHas('expense_items', ['unit_price' => 150, 'quantity' => 2]);
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')->assertJsonPath('total', 750);
        $sjis = mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');
        $this->upload($sjis, 'import', 'SJIS-win')->assertOk()->assertExactJson(['importedCount' => 0, 'alreadyImported' => true]);
        $this->upload("\xEF\xBB\xBF".$csv)->assertJsonPath('alreadyImported', true);
        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseCount('expense_imports', 1);
    }

    public function test_one_invalid_row_rejects_whole_import_with_line_numbers(): void
    {
        $csv = self::HEADER."2026-09-01,スーパー,食費,りんご,150,2\n2026-02-30,カフェ,不明,商品,-1,0\n";
        $this->upload($csv, 'import')->assertUnprocessable()->assertJsonValidationErrors('rows')
            ->assertJsonPath('errors.rows.0', fn ($message) => str_starts_with($message, '3行目:'));
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_imports', 0);
    }

    public function test_database_failure_rolls_back_all_rows_and_import_marker(): void
    {
        ExpenseItem::creating(function ($item) {
            if ($item->name === '失敗') {
                throw new \RuntimeException('Simulated import failure');
            }
        });
        try {
            $this->upload(self::HEADER."2026-09-01,スーパー,食費,成功,150,2\n2026-09-02,カフェ,外食,失敗,450,1\n", 'import')->assertStatus(500);
            $this->assertDatabaseCount('expenses', 0);
            $this->assertDatabaseCount('expense_items', 0);
            $this->assertDatabaseCount('expense_imports', 0);
        } finally {
            ExpenseItem::flushEventListeners();
        }
    }

    public function test_invalid_csv_headers_quotes_columns_and_numbers_are_rejected(): void
    {
        foreach ([
            'incorrect,header',
            self::HEADER,
            self::HEADER."2026-09-01,店,食費,商品,150\n",
            self::HEADER."2026-09-01,店,食費,\"閉じない,150,2\n",
            self::HEADER."2026-09-01,店,食費,商\"品,150,2\n",
            self::HEADER."2026-09-01,店,食費,商品,1.5,2\n",
            self::HEADER."2026-09-01,店,食費,商品,1e2,2\n",
            self::HEADER."2026-09-01,店,食費,商品,100000001,1\n",
        ] as $csv) {
            $this->upload($csv, 'import')->assertUnprocessable();
        }
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_encoding_and_upload_limits_are_validated(): void
    {
        $sjis = mb_convert_encoding(self::HEADER."2026-09-01,店,食費,商品,0,1\n", 'SJIS-win', 'UTF-8');
        $this->upload($sjis)->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->upload($sjis, 'preview', 'SJIS-win')->assertOk()->assertJsonPath('total', 0);
        $this->upload(str_repeat('a', 2048 * 1024 + 1))->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->upload(self::HEADER, 'preview', 'unknown')->assertUnprocessable()->assertJsonValidationErrors('encoding');
        $this->postJson('/api/v1/expense-imports', [])->assertUnprocessable()->assertJsonValidationErrors(['file', 'encoding']);
    }

    public function test_row_limit_preview_limit_and_multiline_field(): void
    {
        $csv = self::HEADER.str_repeat("2026-09-01,店,食費,商品,10,1\n", 1000);
        $this->upload($csv)->assertOk()->assertJsonPath('count', 1000)->assertJsonCount(20, 'rows');
        $this->upload($csv."2026-09-01,店,食費,商品,10,1\n")->assertUnprocessable();
        $this->upload(self::HEADER."2026-09-01,店,食費,\"商品\n説明\",10,1\n2026-09-02,店,食費,商品,10,1\n")
            ->assertOk()->assertJsonPath('rows.1.line', 4);
    }

    public function test_unknown_categories_are_previewed_without_writes_and_require_exact_approval(): void
    {
        $csv = self::HEADER."2026-09-01,店,お酒,ビール,300,1\n2026-09-02,店,酒肴品,するめ,200,1\n2026-09-03,店,お酒,ビール,300,1\n";
        $this->upload($csv)->assertOk()->assertJsonPath('unknownCategories', ['お酒', '酒肴品'])->assertJsonPath('rows.0.categoryName', 'お酒');
        $this->assertDatabaseCount('expense_categories', 9);
        $this->upload($csv, 'import')->assertUnprocessable()->assertJsonValidationErrors('categories');
        $this->upload($csv, 'import', 'UTF-8', ['お酒'])->assertUnprocessable();
        $this->assertDatabaseCount('expense_categories', 9);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_imports', 0);
        $this->upload($csv, 'import', 'UTF-8', ['お酒', '酒肴品'])->assertCreated()->assertJsonPath('importedCount', 3);
        $this->assertDatabaseCount('expense_categories', 11);
        $this->assertDatabaseCount('expenses', 3);
        $this->upload($csv)->assertJsonPath('unknownCategories', [])->assertJsonPath('alreadyImported', true);
        $this->upload($csv, 'import', 'UTF-8', ['お酒', '酒肴品'])->assertOk()->assertJsonPath('alreadyImported', true);
        $this->assertDatabaseCount('expenses', 3);
    }

    public function test_approved_new_categories_are_rolled_back_on_item_failure(): void
    {
        ExpenseItem::creating(fn () => throw new \RuntimeException('Simulated failure'));
        try {
            $this->upload(self::HEADER."2026-09-01,店,お酒,ビール,300,1\n", 'import', 'UTF-8', ['お酒'])->assertStatus(500);
            $this->assertDatabaseCount('expense_categories', 9);
            $this->assertDatabaseCount('expenses', 0);
            $this->assertDatabaseCount('expense_imports', 0);
        } finally {
            ExpenseItem::flushEventListeners();
        }
    }

    public function test_unknown_category_after_preview_limit_is_reported_and_invalid_names_are_rejected(): void
    {
        $csv = self::HEADER.str_repeat("2026-09-01,店,食費,商品,10,1\n", 20)."2026-09-01,店,お酒,ビール,300,1\n";
        $this->upload($csv)->assertJsonCount(20, 'rows')->assertJsonPath('unknownCategories', ['お酒']);
        $this->upload(self::HEADER.'2026-09-01,店,'.str_repeat('酒', 51).",商品,10,1\n")->assertUnprocessable();
        $this->upload(self::HEADER."2026-09-01,店,,商品,10,1\n")->assertUnprocessable();
        $this->assertDatabaseCount('expense_categories', 9);
    }
}
