<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportExpenseCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:2048'],
            'encoding' => ['required', 'in:UTF-8,SJIS-win'],
            'approved_categories' => ['sometimes', 'array', 'max:1000'],
            'approved_categories.*' => ['required', 'string', 'max:50', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'CSVファイルを選択してください。',
            'file.file' => 'ファイルを読み込めませんでした。選び直してください。',
            'file.uploaded' => 'アップロードできませんでした。2MB以内のCSVを選択してください。',
            'file.max' => 'CSVファイルは2MB以内にしてください。',
            'encoding.required' => '文字コードを選択してください。',
            'encoding.in' => '文字コードはUTF-8またはShift_JISを選択してください。',
        ];
    }
}
