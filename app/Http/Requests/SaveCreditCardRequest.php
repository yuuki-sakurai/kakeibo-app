<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCreditCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'closing_day' => ['required', 'integer', 'between:1,31'],
            'payment_day' => ['required', 'integer', 'between:1,31'],
            'payment_month_offset' => ['required', 'integer', 'between:0,2'],
            'bank_account_id' => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}
