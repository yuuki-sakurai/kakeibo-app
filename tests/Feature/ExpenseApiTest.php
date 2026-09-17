<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'date' => '2026-09-06', 'store' => 'スーパー', 'category' => 'food',
            'items' => [
                ['name' => 'りんご', 'unitPrice' => 150, 'quantity' => 2],
                ['name' => 'パン', 'unitPrice' => 200, 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_item_tax_is_saved_and_included_in_detail_and_summary(): void
    {
        $input = $this->payload(['items' => [
            ['name' => '標準', 'unitPrice' => 199, 'quantity' => 2, 'taxable' => true, 'taxRate' => 10],
            ['name' => '軽減', 'unitPrice' => 101, 'quantity' => 1, 'taxable' => true, 'taxRate' => 8],
            ['name' => '加算なし', 'unitPrice' => 500, 'quantity' => 1, 'taxable' => false, 'taxRate' => 10],
        ]]);
        $created = $this->postJson('/api/v1/expenses', $input)->assertCreated()
            ->assertJsonPath('total', 1046)->assertJsonPath('items.0.taxAmount', 39)
            ->assertJsonPath('items.1.taxAmount', 8)->assertJsonPath('items.2.taxAmount', 0)->json();
        $this->assertDatabaseHas('expense_items', ['id' => $created['items'][0]['id'], 'taxable' => 1, 'tax_rate' => 10, 'tax_amount' => 39]);
        $this->getJson('/api/v1/expenses/'.$created['id'])->assertExactJson($created);
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')
            ->assertJsonPath('total', 1046)->assertJsonPath('dailyTotals.0.amount', 1046)
            ->assertJsonPath('categoryTotals.0.amount', 1046);

        $input['items'][0]['taxRate'] = 12.25;
        $other = $this->postJson('/api/v1/expenses', $input)->assertCreated()->assertJsonPath('items.0.taxAmount', 48)->json();
        $this->getJson('/api/v1/expenses/'.$created['id'])->assertExactJson($created);
        $input['items'][0]['taxable'] = false;
        $this->putJson('/api/v1/expenses/'.$other['id'], $input)->assertOk()
            ->assertJsonPath('items.0.taxAmount', 0)->assertJsonPath('total', 1007);
    }

    public function test_invalid_tax_inputs_are_rejected_without_writing(): void
    {
        foreach ([-1, 101, 8.123, 'invalid', null] as $rate) {
            $this->postJson('/api/v1/expenses', $this->payload(['items' => [
                ['name' => '商品', 'unitPrice' => 100, 'quantity' => 1, 'taxable' => true, 'taxRate' => $rate],
            ]]))->assertUnprocessable();
        }
        $this->postJson('/api/v1/expenses', $this->payload(['items' => [
            ['name' => '商品', 'unitPrice' => 100, 'quantity' => 1, 'taxable' => true],
        ]]))->assertUnprocessable();
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_create_persists_items_and_calculates_total_then_lists_by_date(): void
    {
        $created = $this->postJson('/api/v1/expenses', $this->payload(['total' => 1]))
            ->assertCreated()->assertJsonPath('total', 500)->assertJsonPath('items.0.unitPrice', 150)->json();
        $this->assertIsString($created['id']);
        $this->assertIsString($created['items'][0]['id']);
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('expense_items', 2);
        $this->assertDatabaseHas('expense_items', ['expense_id' => $created['id'], 'unit_price' => 150, 'quantity' => 2]);
        $this->getJson('/api/v1/expenses?date=2026-09-06')->assertOk()->assertExactJson([$created]);
        $this->getJson('/api/v1/expenses?date=2026-09-07')->assertOk()->assertExactJson([]);
    }

    public function test_summary_filters_month_counts_expenses_and_groups_categories(): void
    {
        foreach ([[], ['category' => 'daily'], ['date' => '2026-09-30', 'store' => '薬局'], ['date' => '2026-08-31'], ['date' => '2026-10-01']] as $overrides) {
            $this->postJson('/api/v1/expenses', $this->payload($overrides))->assertCreated();
        }
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')->assertOk()->assertExactJson([
            'year' => 2026, 'month' => 9, 'total' => 1500,
            'dailyTotals' => [
                ['date' => '2026-09-06', 'amount' => 1000, 'count' => 2],
                ['date' => '2026-09-30', 'amount' => 500, 'count' => 1],
            ],
            'categoryTotals' => [
                ['category' => 'food', 'amount' => 1000, 'percentage' => 66.7],
                ['category' => 'daily', 'amount' => 500, 'percentage' => 33.3],
            ],
        ]);
        $this->getJson('/api/v1/stores')->assertOk()->assertExactJson(['スーパー', '薬局']);
    }

    public function test_empty_month_and_zero_price_are_supported(): void
    {
        $this->getJson('/api/v1/monthly-summary?year=2026&month=2')->assertExactJson([
            'year' => 2026, 'month' => 2, 'total' => 0, 'dailyTotals' => [], 'categoryTotals' => [],
        ]);
        $this->postJson('/api/v1/expenses', $this->payload([
            'items' => [['name' => '無料', 'unitPrice' => 0, 'quantity' => 1]],
        ]))->assertCreated()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')->assertOk()->assertJsonPath('categoryTotals.0.percentage', 0);
    }

    public function test_invalid_inputs_do_not_write_any_rows(): void
    {
        foreach ([['date' => '2026-02-30'], ['date' => '0999-01-01'], ['store' => '  '], ['category' => 'unknown'], ['items' => []], ['items' => [['name' => '商品', 'unitPrice' => -1, 'quantity' => 0]]], ['items' => [['name' => '商品', 'unitPrice' => 1.5, 'quantity' => 1]]]] as $overrides) {
            $this->postJson('/api/v1/expenses', $this->payload($overrides))->assertUnprocessable()->assertJsonStructure(['message', 'errors']);
        }
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_items', 0);
        $this->getJson('/api/v1/expenses')->assertUnprocessable();
        $this->getJson('/api/v1/monthly-summary?year=2026&month=13')->assertUnprocessable();
    }

    public function test_edit_updates_detail_and_moves_summary_without_duplicating_expense(): void
    {
        $original = $this->postJson('/api/v1/expenses', $this->payload())->assertCreated()->json();
        $url = '/api/v1/expenses/'.$original['id'];
        $this->getJson($url)->assertExactJson($original);
        $changed = $this->payload(['date' => '2026-10-01', 'store' => 'コンビニ', 'category' => 'leisure',
            'items' => [['name' => 'ビール', 'unitPrice' => 230, 'quantity' => 3]]]);
        $this->putJson($url, $changed)->assertOk()->assertJsonPath('id', $original['id'])
            ->assertJsonPath('category', 'leisure')->assertJsonPath('items.0.name', 'ビール')->assertJsonPath('total', 690);
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('expense_items', 1);
        $this->getJson('/api/v1/expenses?date=2026-09-06')->assertExactJson([]);
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')->assertJsonPath('total', 0);
        $this->getJson('/api/v1/monthly-summary?year=2026&month=10')->assertJsonPath('total', 690)->assertJsonPath('categoryTotals.0.category', 'leisure');
        $this->getJson('/api/v1/expenses/99999')->assertNotFound();
        $this->putJson('/api/v1/expenses/99999', $changed)->assertNotFound();
    }

    public function test_invalid_or_failed_update_preserves_original_items(): void
    {
        $original = $this->postJson('/api/v1/expenses', $this->payload())->assertCreated()->json();
        $url = '/api/v1/expenses/'.$original['id'];
        foreach ([['category' => 'unknown'], ['items' => []], ['date' => '2026-02-30']] as $overrides) {
            $this->putJson($url, $this->payload($overrides))->assertUnprocessable();
            $this->getJson($url)->assertExactJson($original);
        }
        ExpenseItem::creating(function () {
            throw new \RuntimeException('Simulated update failure');
        });
        try {
            $this->putJson($url, $this->payload(['store' => '変更']))->assertStatus(500);
            $this->getJson($url)->assertExactJson($original);
        } finally {
            ExpenseItem::flushEventListeners();
        }
    }

    public function test_imported_expense_can_be_edited_and_reimport_does_not_overwrite_it(): void
    {
        $csv = "日付,店舗,カテゴリ,品目,単価,数量\n2026-09-06,店,食費,商品,100,1\n";
        $upload = fn () => ['file' => UploadedFile::fake()->createWithContent('data.csv', $csv), 'encoding' => 'UTF-8'];
        $this->post('/api/v1/expense-imports', $upload(), ['Accept' => 'application/json'])->assertCreated();
        $id = Expense::firstOrFail()->id;
        $this->putJson('/api/v1/expenses/'.$id, $this->payload(['category' => 'leisure']))->assertOk();
        $this->post('/api/v1/expense-imports', $upload(), ['Accept' => 'application/json'])->assertOk()->assertJsonPath('alreadyImported', true);
        $this->assertDatabaseCount('expenses', 1);
        $this->getJson('/api/v1/expenses/'.$id)->assertJsonPath('category', 'leisure')->assertJsonPath('total', 500);
    }

    public function test_item_failure_rolls_back_parent_and_previous_items(): void
    {
        ExpenseItem::creating(function (ExpenseItem $item) {
            if ($item->name === 'パン') {
                throw new \RuntimeException('Simulated item failure');
            }
        });
        try {
            $this->postJson('/api/v1/expenses', $this->payload())->assertStatus(500);
            $this->assertDatabaseCount('expenses', 0);
            $this->assertDatabaseCount('expense_items', 0);
        } finally {
            ExpenseItem::flushEventListeners();
        }
    }
}
