<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCreditTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:9998-12'],
            'credit_card_id' => ['nullable', 'integer', Rule::exists('credit_cards', 'id')->where('user_id', $this->user()->id)],
            'search' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
