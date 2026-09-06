<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $expense = Expense::create([
                'date' => $data['date'],
                'store' => $data['store'],
                'category_id' => $data['category'],
            ]);
            $expense->items()->createMany(array_map(fn ($item) => [
                'name' => $item['name'],
                'unit_price' => $item['unitPrice'],
                'quantity' => $item['quantity'],
            ], $data['items']));

            return $expense->load('items');
        });
    }

    public function monthlySummary(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime($start)));
        // Aggregate item rows in SQL; count each expense only once.
        $rows = DB::table('expenses')
            ->join('expense_items', 'expenses.id', '=', 'expense_items.expense_id')
            ->whereBetween('expenses.date', [$start, $end])
            ->select('expenses.date', 'expenses.category_id')
            ->selectRaw('SUM(expense_items.unit_price * expense_items.quantity) AS amount, COUNT(DISTINCT expenses.id) AS expense_count')
            ->groupBy('expenses.date', 'expenses.category_id')
            ->orderBy('expenses.date')->orderBy('expenses.category_id')->get();
        $total = (int) $rows->sum('amount');
        $daily = $rows->groupBy('date')->map(fn ($group, $date) => [
            'date' => $date, 'amount' => (int) $group->sum('amount'), 'count' => (int) $group->sum('expense_count'),
        ])->values()->all();
        $categories = $rows->groupBy('category_id')->map(function ($group, $category) use ($total) {
            $amount = (int) $group->sum('amount');

            return ['category' => $category, 'amount' => $amount, 'percentage' => $total === 0 ? 0 : round($amount / $total * 100, 1)];
        })->sortByDesc('amount')->values()->all();

        return ['year' => $year, 'month' => $month, 'total' => $total, 'dailyTotals' => $daily, 'categoryTotals' => $categories];
    }
}
