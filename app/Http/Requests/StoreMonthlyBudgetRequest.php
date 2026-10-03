<?php

namespace App\Http\Requests;

use App\Services\ExpenseAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlyBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryIds = ExpenseAccess::categories()->pluck('id')->all();

        return [
            'year' => ['required', 'integer', 'between:1000,9999'],
            'month' => ['required', 'integer', 'between:1,12'],
            'amount' => ['present', 'nullable', 'integer', 'between:0,999999999999'],
            'categoryBudgets' => ['present', 'array', 'max:100'],
            'categoryBudgets.*.category' => ['required', 'string', 'distinct:strict', Rule::in($categoryIds)],
            'categoryBudgets.*.amount' => ['required', 'integer', 'between:0,999999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.*' => '年を正しく指定してください。',
            'month.*' => '月は1から12の範囲で指定してください。',
            'amount.*' => '月の予算は0円以上で入力してください。',
            'categoryBudgets.array' => 'カテゴリ別予算の形式が正しくありません。',
            'categoryBudgets.max' => 'カテゴリ別予算は100件以内で指定してください。',
            'categoryBudgets.*.category.in' => '登録済みのカテゴリを選択してください。',
            'categoryBudgets.*.category.distinct' => '同じカテゴリを複数回指定できません。',
            'categoryBudgets.*.amount.*' => 'カテゴリ別予算は0円以上で入力してください。',
        ];
    }
}
