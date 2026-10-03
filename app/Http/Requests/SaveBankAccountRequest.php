<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'account_type' => ['required', 'in:普通,当座,貯蓄'],
            'balance' => ['required', 'integer', 'between:-999999999999,999999999999'],
            'color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ];
    }
}
