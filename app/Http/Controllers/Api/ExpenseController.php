<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseAccess;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request, ExpenseService $service): JsonResponse
    {
        $expense = $service->create($request->validated());

        return response()->json((new ExpenseResource($expense))->resolve($request), 201);
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        return response()->json((new ExpenseResource($this->owned($expense)->load('items')))->resolve($request));
    }

    public function update(StoreExpenseRequest $request, Expense $expense, ExpenseService $service): JsonResponse
    {
        return response()->json((new ExpenseResource($service->update($this->owned($expense), $request->validated())))->resolve($request));
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31']]);
        $expenses = Expense::with('items')->where('user_id', ExpenseAccess::userId())->where('date', $data['date'])->orderBy('id')->get();

        return response()->json(ExpenseResource::collection($expenses)->resolve($request));
    }

    public function summary(Request $request, ExpenseService $service): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:1000,9999'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        return response()->json($service->monthlySummary((int) $data['year'], (int) $data['month']));
    }

    private function owned(Expense $expense): Expense
    {
        abort_unless($expense->user_id === ExpenseAccess::userId(), 404);

        return $expense;
    }

    public function stores(): JsonResponse
    {
        return response()->json(Expense::query()->where('user_id', ExpenseAccess::userId())->select('store')->distinct()->orderBy('store')->pluck('store'));
    }
}
