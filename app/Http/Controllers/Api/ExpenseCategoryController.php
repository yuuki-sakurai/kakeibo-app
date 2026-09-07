<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseCategoryRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(DB::table('expense_categories')->select('id', 'name')->orderBy('name')->get());
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $category = ['id' => (string) Str::ulid(), 'name' => $request->validated('name')];
        try {
            DB::table('expense_categories')->insert($category);
        } catch (UniqueConstraintViolationException $error) {
            if (! DB::table('expense_categories')->where('name', $category['name'])->exists()) {
                throw $error;
            }
            throw ValidationException::withMessages(['name' => 'このカテゴリ名はすでに使用されています。']);
        }

        return response()->json($category, 201);
    }
}
