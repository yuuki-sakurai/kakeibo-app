<?php

namespace Tests\Feature;

use App\Models\ExpenseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

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
