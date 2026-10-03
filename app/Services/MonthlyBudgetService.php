<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MonthlyBudgetService
{
    public function get(int $year, int $month): array
    {
        $budget = DB::table('monthly_budgets')
            ->where('user_id', ExpenseAccess::userId())
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $budget) {
            return ['year' => $year, 'month' => $month, 'amount' => null, 'categoryBudgets' => []];
        }

        $categories = DB::table('monthly_category_budgets')
            ->where('monthly_budget_id', $budget->id)
            ->orderBy('category_id')
            ->get(['category_id', 'amount'])
            ->map(fn ($item) => ['category' => $item->category_id, 'amount' => (int) $item->amount])
            ->all();

        return [
            'year' => (int) $budget->year,
            'month' => (int) $budget->month,
            'amount' => $budget->amount === null ? null : (int) $budget->amount,
            'categoryBudgets' => $categories,
        ];
    }

    public function save(array $data): array
    {
        $userId = ExpenseAccess::userId();

        DB::transaction(function () use ($data, $userId) {
            $budget = DB::table('monthly_budgets')
                ->where('user_id', $userId)
                ->where('year', $data['year'])
                ->where('month', $data['month'])
                ->lockForUpdate()
                ->first();
            $now = now();

            if ($budget) {
                DB::table('monthly_budgets')->where('id', $budget->id)->update([
                    'amount' => $data['amount'],
                    'updated_at' => $now,
                ]);
                $budgetId = $budget->id;
            } else {
                $budgetId = DB::table('monthly_budgets')->insertGetId([
                    'user_id' => $userId,
                    'year' => $data['year'],
                    'month' => $data['month'],
                    'amount' => $data['amount'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('monthly_category_budgets')->where('monthly_budget_id', $budgetId)->delete();
            $rows = array_map(fn ($category) => [
                'monthly_budget_id' => $budgetId,
                'category_id' => $category['category'],
                'amount' => $category['amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $data['categoryBudgets']);

            if ($rows) {
                DB::table('monthly_category_budgets')->insert($rows);
            }
        });

        return $this->get((int) $data['year'], (int) $data['month']);
    }
}
