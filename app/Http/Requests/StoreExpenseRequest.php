<?php

namespace App\Http\Requests;

use App\Services\ExpenseAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'store' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! ExpenseAccess::categories()->where('id', $value)->exists()) {
                    $fail('カテゴリを選択してください。');
                }
            }],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:name,unitPrice,quantity'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unitPrice' => ['required', 'integer', 'min:0', 'max:100000000'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
