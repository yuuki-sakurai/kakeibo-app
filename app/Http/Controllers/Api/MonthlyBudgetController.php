<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonthlyBudgetRequest;
use App\Services\MonthlyBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonthlyBudgetController extends Controller
{
    public function show(Request $request, MonthlyBudgetService $service): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:1000,9999'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        return response()->json($service->get((int) $data['year'], (int) $data['month']));
    }

    public function update(StoreMonthlyBudgetRequest $request, MonthlyBudgetService $service): JsonResponse
    {
        return response()->json($service->save($request->validated()));
    }
}
