<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_users_cannot_read_update_or_use_private_categories(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first);
        $category = $this->postJson('/api/v1/categories', ['name' => 'お酒'])->assertCreated()->json('id');
        $data = ['date' => '2026-09-07', 'store' => '秘密の店', 'category' => $category, 'items' => [['name' => '品物', 'unitPrice' => 300, 'quantity' => 1]]];
        $id = $this->postJson('/api/v1/expenses', $data)->assertCreated()->json('id');
        $this->actingAs($second);
        $this->getJson('/api/v1/expenses/'.$id)->assertNotFound();
        $this->putJson('/api/v1/expenses/'.$id, array_replace($data, ['category' => 'food']))->assertNotFound();
        $this->postJson('/api/v1/expenses', $data)->assertUnprocessable();
        $this->getJson('/api/v1/expenses?date=2026-09-07')->assertExactJson([]);
        $this->getJson('/api/v1/stores')->assertExactJson([]);
        $this->getJson('/api/v1/monthly-summary?year=2026&month=9')->assertJsonPath('total', 0);
        $this->getJson('/api/v1/categories')->assertJsonMissing(['id' => $category]);
        $this->postJson('/api/v1/categories', ['name' => 'お酒'])->assertCreated();
        $this->assertDatabaseHas('expenses', ['id' => $id, 'user_id' => $first->id]);
    }

    public function test_csv_history_and_custom_categories_are_user_scoped(): void
    {
        $csv = "日付,店舗,カテゴリ,品目,単価,数量\n2026-09-07,店,食費,品物,100,1\n";
        $upload = fn () => ['file' => UploadedFile::fake()->createWithContent('data.csv', $csv), 'encoding' => 'UTF-8'];
        foreach ([User::factory()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user);
            $this->post('/api/v1/expense-imports/preview', $upload(), ['Accept' => 'application/json'])->assertOk()->assertJsonPath('alreadyImported', false);
            $this->post('/api/v1/expense-imports', $upload(), ['Accept' => 'application/json'])->assertCreated();
            $this->post('/api/v1/expense-imports', $upload(), ['Accept' => 'application/json'])->assertOk()->assertJsonPath('alreadyImported', true);
        }
        $this->assertDatabaseCount('expense_imports', 2);
    }

    public function test_legacy_data_is_hidden_until_explicit_assignment(): void
    {
        $user = User::factory()->create();
        DB::table('expense_categories')->insert(['id' => 'legacy', 'name' => '旧カテゴリ']);
        DB::table('expenses')->insert(['date' => '2026-09-07', 'store' => '旧店舗', 'category_id' => 'legacy']);
        $this->actingAs($user);
        $this->getJson('/api/v1/stores')->assertExactJson([]);
        $this->getJson('/api/v1/categories')->assertJsonMissing(['id' => 'legacy']);
        $this->artisan('expenses:assign-legacy', ['email' => $user->email])->assertSuccessful();
        $this->getJson('/api/v1/stores')->assertExactJson(['旧店舗']);
        $this->getJson('/api/v1/categories')->assertJsonFragment(['id' => 'legacy']);
    }
}
