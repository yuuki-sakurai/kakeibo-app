<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportExpenseCsvRequest;
use App\Services\ExpenseCsvService;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;

class ExpenseCsvController extends Controller
{
    public function preview(ImportExpenseCsvRequest $request, ExpenseCsvService $csv): JsonResponse
    {
        return response()->json($csv->preview($csv->parse($request->file('file'), $request->validated('encoding'), true)));
    }

    public function store(ImportExpenseCsvRequest $request, ExpenseCsvService $csv, ExpenseService $expenses): JsonResponse
    {
        $result = $csv->import($csv->parse($request->file('file'), $request->validated('encoding'), true), $expenses, $request->validated('approved_categories', []));

        return response()->json($result, $result['alreadyImported'] ? 200 : 201);
    }
}
