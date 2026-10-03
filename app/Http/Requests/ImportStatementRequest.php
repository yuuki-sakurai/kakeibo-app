<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'credit_card_id' => ['required', 'integer', Rule::exists('credit_cards', 'id')->where('user_id', $this->user()->id)],
            'file' => ['required', 'file', 'max:2048', 'extensions:csv'],
        ];
    }
}
