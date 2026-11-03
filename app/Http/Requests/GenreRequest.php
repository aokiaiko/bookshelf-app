<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    /**
     * リクエストを許可するか判定
     *
     * @return bool リクエストの許可結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを定義
     *
     * @return array バリデーションルールの配列
     */
    public function rules(): array
    {
        $genre = $this->route('genre');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('genres', 'name')->ignore($genre?->id),
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     *
     * @return array バリデーションメッセージの配列
     */
    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名は必須です。',
            'name.string' => 'ジャンル名は文字列で入力してください。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'そのジャンル名は既に使用されています。',
        ];
    }
}
