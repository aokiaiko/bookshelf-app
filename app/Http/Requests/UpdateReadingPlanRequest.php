<?php

namespace App\Http\Requests;

use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReadingPlanRequest extends FormRequest
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
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * 基本バリデーション後に読書計画の重複を検証
     *
     * @return array 追加のバリデーション処理
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $readingPlan = $this->route('readingPlan');

                $exists = ReadingPlan::where('user_id', $this->user()->id)
                    ->where('book_id', $readingPlan->book_id)
                    ->where('status', 'in_progress')
                    ->where('id', '!=', $readingPlan->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'target_date',
                        'この書籍は既に進行中の読書計画が存在します。'
                    );
                }
            },
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
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
