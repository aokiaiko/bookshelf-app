<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'rating.required' => '評価は必須です。',
            'rating.integer' => '評価は1〜5の整数で入力してください。',
            'rating.between' => '評価は1〜5の整数で入力してください。',
            'comment.required' => 'コメントは必須です。',
            'comment.string' => 'コメントは文字列で入力してください。',
        ];
    }
}
