<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreditCardApiTest extends TestCase
{
    use RefreshDatabase;

    private function card(array $overrides = []): array
    {
        return $this->postJson('/api/v1/credit-cards', array_replace([
            'name' => 'テストカード', 'closing_day' => 31, 'payment_day' => 27,
            'payment_month_offset' => 1, 'bank_account_id' => null,
        ], $overrides))->assertCreated()->json();
    }

    private function upload(int $id, string $rows, string $name = 'statement.csv')
    {
        return $this->postJson('/api/v1/statement-imports', [
            'credit_card_id' => $id,
            'file' => UploadedFile::fake()->createWithContent($name, "利用日,利用場所,カテゴリ,金額,支払予定月\n".$rows),
        ]);
    }

    public function test_all_card_endpoints_require_authentication(): void
    {
        foreach (['bank-accounts', 'credit-cards', 'credit-card-transactions?month=2026-10', 'credit-card-overview?month=2026-10', 'statement-imports'] as $path) {
            $this->getJson('/api/v1/'.$path)->assertUnauthorized();
        }
        foreach (['bank-accounts', 'credit-cards', 'statement-imports'] as $path) {
            $this->postJson('/api/v1/'.$path, [])->assertUnauthorized();
        }
    }

    public function test_card_and_account_updates_are_owned_and_validated(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->actingAs($alice);
        $input = ['bank_name' => '銀行', 'branch_name' => '', 'account_type' => '普通', 'balance' => 123456, 'color' => '#6255d9'];
        $account = $this->postJson('/api/v1/bank-accounts', $input)->assertCreated()->json();
        $card = $this->card(['bank_account_id' => $account['id']]);
        $this->putJson('/api/v1/credit-cards/'.$card['id'], array_replace($card, ['name' => '更新', 'user_id' => $bob->id]))->assertOk()->assertJsonPath('user_id', $alice->id);
        $this->putJson('/api/v1/bank-accounts/'.$account['id'], array_replace($input, ['balance' => 999]))->assertOk()->assertJsonPath('balance', 999);
        $this->actingAs($bob);
        $this->getJson('/api/v1/credit-cards')->assertExactJson([]);
        $this->getJson('/api/v1/bank-accounts')->assertExactJson([]);
        $this->postJson('/api/v1/credit-cards', $card)->assertUnprocessable();
        $card['bank_account_id'] = null;
        $this->putJson('/api/v1/credit-cards/'.$card['id'], $card)->assertNotFound();
        $this->putJson('/api/v1/bank-accounts/'.$account['id'], $input)->assertNotFound();
        $this->upload($card['id'], '2026-10-01,店,,100,2026-11')->assertUnprocessable();
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10&credit_card_id='.$card['id'])->assertUnprocessable();
        $this->postJson('/api/v1/credit-cards', array_replace($card, ['closing_day' => 32]))->assertUnprocessable();
        $this->postJson('/api/v1/bank-accounts', array_replace($input, ['color' => 'url(bad)']))->assertUnprocessable();
    }

    public function test_csv_import_persists_separate_card_transactions_and_is_idempotent(): void
    {
        $this->actingAs(User::factory()->create());
        $card = $this->card();
        $rows = "2026-10-01,店,,1000,2026-11\n2026-10-02,返金,,-200,2026-11\n";
        $this->upload($card['id'], $rows)->assertCreated()->assertJsonPath('import.imported_count', 2)->assertJsonPath('alreadyImported', false);
        $this->upload($card['id'], $rows, 'renamed.csv')->assertOk()->assertJsonPath('alreadyImported', true);
        $this->assertDatabaseCount('credit_card_transactions', 2);
        $this->assertDatabaseCount('statement_imports', 1);
        $this->assertDatabaseCount('expenses', 0);
        $this->getJson('/api/v1/credit-card-overview?month=2026-10')->assertOk()->assertJsonPath('total', 800)->assertJsonPath('count', 2)->assertJsonPath('payments.0.amount', 800);
        $this->getJson('/api/v1/statement-imports')->assertJsonPath('total', 1)->assertJsonPath('data.0.card_name', 'テストカード');
    }

    public function test_invalid_rows_fail_atomically_and_do_not_reserve_fingerprint(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->card()['id'];
        foreach ([
            '2026-02-30,店,,100,2026-11', '2026-10-01,店,,100,2026-13',
            '2026-10-01,店,,1.5,2026-11', '2026-10-01,,,-1,2026-11',
            '2026-10-01,店,未登録,100,2026-11', '2026-10-01,店,,100,2026-11,余分',
        ] as $invalid) {
            $this->upload($id, "2026-10-01,正常,,100,2026-11\n".$invalid)->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->upload($id, '')->assertUnprocessable();
        $this->assertDatabaseCount('credit_card_transactions', 0);
        $this->assertDatabaseCount('statement_imports', 0);
    }

    public function test_categories_and_all_read_endpoints_are_isolated_by_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        DB::table('expense_categories')->insert(['id' => 'alice-category', 'name' => '私専用', 'user_id' => $alice->id]);
        $this->actingAs($alice);
        $this->upload($this->card()['id'], '2026-10-01,店,私専用,200,2026-10')->assertCreated();
        $this->actingAs($bob);
        $id = $this->card()['id'];
        $this->upload($id, '2026-10-01,店,私専用,100,2026-11')->assertUnprocessable();
        $this->upload($id, '2026-10-01,Bob,,500,2026-11')->assertCreated();
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10')->assertJsonPath('total', 1)->assertJsonPath('data.0.merchant', 'Bob');
        $this->getJson('/api/v1/credit-card-overview?month=2026-10')->assertJsonPath('total', 500)->assertJsonCount(1, 'payments')->assertJsonPath('payments.0.amount', 500);
        $this->getJson('/api/v1/statement-imports')->assertJsonPath('total', 1);
    }

    public function test_month_date_card_search_and_pagination_do_not_truncate_aggregates(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->card()['id'];
        $this->upload($id, str_repeat("2026-10-01,店舗100%,,100,2026-11\n", 55)."2026-09-30,店,,999,2026-10\n")->assertCreated();
        $this->upload($this->card(['name' => '別カード'])['id'], '2026-10-02,店,,200,2026-12')->assertCreated();
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10')->assertJsonPath('total', 56)->assertJsonCount(50, 'data');
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10&page=2')->assertJsonCount(6, 'data');
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10&date=2026-10-02')->assertJsonPath('total', 1);
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10&search=%25')->assertJsonPath('total', 55);
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10&credit_card_id='.$id)->assertJsonPath('total', 55);
        $this->getJson('/api/v1/credit-card-overview?month=2026-10')->assertJsonPath('total', 5700)->assertJsonPath('dailyTotals.0.amount', 5500);
        $this->getJson('/api/v1/credit-card-transactions?month=invalid')->assertUnprocessable();
        $this->getJson('/api/v1/statement-imports?page=0')->assertUnprocessable();
    }

    public function test_shift_jis_and_utf8_bom_are_supported(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->card()['id'];
        $csv = "利用日,利用場所,カテゴリ,金額,支払予定月\n2026-10-01,喫茶店,,500,2026-11\n";
        foreach (["\xEF\xBB\xBF".$csv, mb_convert_encoding($csv, 'SJIS-win', 'UTF-8')] as $bytes) {
            $this->postJson('/api/v1/statement-imports', ['credit_card_id' => $id, 'file' => UploadedFile::fake()->createWithContent('data.csv', $bytes)])->assertCreated();
        }
        $this->getJson('/api/v1/credit-card-transactions?month=2026-10')->assertJsonPath('data.0.merchant', '喫茶店');
    }
}
