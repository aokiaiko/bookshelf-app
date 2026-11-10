<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingPlanRequest extends FormRequest
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
        return [
            'book_id' => [
                'required',
                'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where('user_id', $this->user()->id)
                    ->where('status', 'in_progress'),
            ],
            'target_date' => ['required', 'date'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義
     *
     * @return array バリデーションエラーメッセージの配列
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '選択された書籍が存在しません。',
            'book_id.unique' => 'この書籍は既に進行中の読書計画が存在します。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
        ];
    }
}
