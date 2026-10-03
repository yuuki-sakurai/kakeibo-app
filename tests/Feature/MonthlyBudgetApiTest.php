<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonthlyBudgetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_unconfigured_month_returns_an_empty_budget(): void
    {
        $this->getJson('/api/v1/monthly-budget?year=2026&month=10')->assertOk()->assertExactJson([
            'year' => 2026, 'month' => 10, 'amount' => null, 'categoryBudgets' => [],
        ]);
    }

    public function test_budget_can_be_created_and_updated_without_leaking_to_another_user(): void
    {
        $this->putJson('/api/v1/monthly-budget', [
            'year' => 2026, 'month' => 10, 'amount' => 80000,
            'categoryBudgets' => [['category' => 'food', 'amount' => 30000], ['category' => 'leisure', 'amount' => 10000]],
        ])->assertOk()->assertExactJson([
            'year' => 2026, 'month' => 10, 'amount' => 80000,
            'categoryBudgets' => [['category' => 'food', 'amount' => 30000], ['category' => 'leisure', 'amount' => 10000]],
        ]);
        $this->assertDatabaseCount('monthly_budgets', 1);
        $this->assertDatabaseCount('monthly_category_budgets', 2);

        $this->putJson('/api/v1/monthly-budget', [
            'year' => 2026, 'month' => 10, 'amount' => 90000,
            'categoryBudgets' => [['category' => 'food', 'amount' => 35000]],
        ])->assertOk()->assertJsonPath('amount', 90000)->assertJsonPath('categoryBudgets.0.amount', 35000);
        $this->assertDatabaseCount('monthly_budgets', 1);
        $this->assertDatabaseCount('monthly_category_budgets', 1);

        $owner = User::query()->firstOrFail();
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/v1/monthly-budget?year=2026&month=10')->assertExactJson([
            'year' => 2026, 'month' => 10, 'amount' => null, 'categoryBudgets' => [],
        ]);
        $this->actingAs($owner);
        $this->getJson('/api/v1/monthly-budget?year=2026&month=10')->assertJsonPath('amount', 90000);
    }

    public function test_invalid_period_amount_or_category_is_rejected(): void
    {
        $otherUser = User::factory()->create();
        DB::table('expense_categories')->insert([
            'id' => 'other-user-cat', 'name' => '別ユーザー用', 'user_id' => $otherUser->id,
        ]);
        $valid = ['year' => 2026, 'month' => 10, 'amount' => null, 'categoryBudgets' => []];
        foreach ([
            ['month' => 13], ['amount' => -1], ['amount' => 'not-a-number'],
            ['categoryBudgets' => [['category' => 'other-user-cat', 'amount' => 500]]],
            ['categoryBudgets' => [['category' => 'food', 'amount' => 500], ['category' => 'food', 'amount' => 600]]],
        ] as $overrides) {
            $this->putJson('/api/v1/monthly-budget', array_replace($valid, $overrides))->assertUnprocessable();
        }
        $this->assertDatabaseCount('monthly_budgets', 0);
        $this->assertDatabaseCount('monthly_category_budgets', 0);
    }

    public function test_budget_endpoints_require_authentication(): void
    {
        auth()->logout();
        $this->getJson('/api/v1/monthly-budget?year=2026&month=10')->assertUnauthorized();
        $this->putJson('/api/v1/monthly-budget', [
            'year' => 2026, 'month' => 10, 'amount' => 1, 'categoryBudgets' => [],
        ])->assertUnauthorized();
    }
}
