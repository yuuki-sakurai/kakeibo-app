<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
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

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31']]);
        $expenses = Expense::with('items')->where('date', $data['date'])->orderBy('id')->get();

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

    public function stores(): JsonResponse
    {
        return response()->json(Expense::query()->select('store')->distinct()->orderBy('store')->pluck('store'));
    }
}
