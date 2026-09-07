<?php

namespace App\Http\Requests;

use App\Services\ExpenseAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => preg_replace('/\A[\s\p{Z}]+|[\s\p{Z}]+\z/u', '', $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:50', 'not_regex:/[\x00-\x1F\x7F]/', function ($attribute, $value, $fail) {
            if (ExpenseAccess::categories()->where(fn ($q) => $q->where('name', $value)->orWhere('id', $value))->exists()) {
                $fail('このカテゴリ名はすでに使用されています。');
            }
        }]];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'カテゴリ名を入力してください。',
            'name.string' => 'カテゴリ名は文字列で入力してください。',
            'name.max' => 'カテゴリ名は50文字以内で入力してください。',
            'name.not_regex' => 'カテゴリ名に改行や制御文字は使用できません。',
            'name.unique' => 'このカテゴリ名はすでに使用されています。別の名前を入力してください。',
        ];
    }
}
