<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_category_is_persisted_and_available_for_expenses_csv_and_summary(): void
    {
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(9)->assertJsonFragment(['id' => 'food', 'name' => '食費']);
        $category = $this->postJson('/api/v1/categories', ['name' => '　お酒　'])->assertCreated()->assertJsonPath('name', 'お酒')->json();
        $this->assertDatabaseHas('expense_categories', $category);
        $this->getJson('/api/v1/categories')->assertJsonCount(10)->assertJsonFragment($category);
        $this->postJson('/api/v1/expenses', [
            'date' => '2026-09-07', 'store' => '酒屋', 'category' => $category['id'],
            'items' => [['name' => '日本酒', 'unitPrice' => 1200, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('category', $category['id']);
        $file = UploadedFile::fake()->createWithContent('expenses.csv', "日付,店舗,カテゴリ,品目,単価,数量\n2026-09-07,酒屋,お酒,ビール,300,2\n");
        $this->post('/api/v1/expense-imports', ['file' => $file, 'encoding' => 'UTF-8'], ['Accept' => 'application/json'])->assertCreated();
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')->assertJsonPath('total', 1800)->assertJsonPath('categoryTotals.0.category', $category['id']);
        $this->getJson('/api/v1/expenses?date=2026-09-07')->assertJsonCount(2)->assertJsonPath('1.category', $category['id']);
    }

    public function test_duplicates_invalid_names_and_existing_ids_are_rejected(): void
    {
        $this->postJson('/api/v1/categories', ['name' => '酒肴品'])->assertCreated();
        foreach (['酒肴品', ' 酒肴品 ', '食費', 'food', '', '　 ', str_repeat('酒', 51), "酒\n肴", ['invalid']] as $name) {
            $this->postJson('/api/v1/categories', ['name' => $name])->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertDatabaseCount('expense_categories', 10);
    }
}
