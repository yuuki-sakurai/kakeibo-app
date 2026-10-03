<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CreditCardService
{
    public function owned(string $table): Builder
    {
        return DB::table($table)->where($table.'.user_id', ExpenseAccess::userId());
    }

    public function save(string $table, array $data, ?int $id): object
    {
        $data['updated_at'] = now();
        if ($id !== null) {
            abort_unless($this->owned($table)->where('id', $id)->exists(), 404);
            $this->owned($table)->where('id', $id)->update($data);
        } else {
            $id = DB::table($table)->insertGetId($data + ['user_id' => ExpenseAccess::userId(), 'created_at' => now()]);
        }

        return $this->owned($table)->where('id', $id)->first();
    }

    private function transactions(array $filters): Builder
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $filters['month']);

        return $this->owned('credit_card_transactions')
            ->whereBetween('date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->when($filters['credit_card_id'] ?? null, fn ($q, $id) => $q->where('credit_card_id', $id))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->where('date', $date))
            ->when(isset($filters['search']) && $filters['search'] !== '', fn ($q) => $q->whereRaw("merchant LIKE ? ESCAPE '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%']));
    }

    public function list(array $filters): array
    {
        $query = $this->transactions($filters);
        $total = (clone $query)->count();
        $page = (int) ($filters['page'] ?? 1);
        $rows = $query->join('credit_cards as cards', 'cards.id', '=', 'credit_card_transactions.credit_card_id')
            ->leftJoin('expense_categories as categories', 'categories.id', '=', 'credit_card_transactions.category_id')
            ->select('credit_card_transactions.*', 'cards.name as card_name', 'categories.name as category_name')
            ->orderByDesc('date')->orderByDesc('credit_card_transactions.id')->offset(($page - 1) * 50)->limit(50)->get();

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => 50];
    }

    public function overview(array $filters): array
    {
        $query = $this->transactions(['month' => $filters['month']]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $filters['month']);
        $daily = (clone $query)->selectRaw('date, SUM(amount) as amount')->groupBy('date')->orderBy('date')->get();
        $categories = (clone $query)->leftJoin('expense_categories as categories', 'categories.id', '=', 'category_id')
            ->selectRaw('categories.name as name, SUM(amount) as amount')->groupBy('categories.name')->orderByDesc('amount')->get();
        $payments = $this->owned('credit_card_transactions')
            ->whereBetween('payment_month', [$month->toDateString(), $month->addMonths(2)->toDateString()])
            ->join('credit_cards as cards', 'cards.id', '=', 'credit_card_id')
            ->leftJoin('bank_accounts as accounts', 'accounts.id', '=', 'cards.bank_account_id')
            ->selectRaw('payment_month, cards.id as card_id, cards.name as card_name, cards.payment_day, accounts.bank_name, SUM(amount) as amount')
            ->groupBy('payment_month', 'cards.id', 'cards.name', 'cards.payment_day', 'accounts.bank_name')->orderBy('payment_month')->get();

        return ['total' => (int) (clone $query)->sum('amount'), 'count' => (clone $query)->count(), 'dailyTotals' => $daily, 'categoryTotals' => $categories, 'payments' => $payments];
    }
}
