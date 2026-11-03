<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
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
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required', 'string', 'size:13',
                Rule::unique('books', 'isbn')->ignore($book?->id),
            ],
            'published_date' => ['required', 'date'],
            'genres' => ['required', 'array', 'min:1'],
            'description' => ['nullable'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
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
            'title.required' => 'タイトルは必須です。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名は必須です。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.required' => 'ISBNは必須です。',
            'isbn.string' => 'ISBNは文字列で入力してください。',
            'isbn.size' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => 'そのISBNは既に使用されています。',

            'genres.required' => 'ジャンルは1つ以上選択してください。',
            'genres.min' => 'ジャンルは1つ以上選択してください。',

            'image_url.url' => '画像URLは有効なURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',

            'published_date.required' => '出版日は必須です。',
            'published_date.date' => '出版日は有効な日付形式で入力してください。',

            'user_id.required' => '登録者IDは必須です。',
            'user_id.integer' => '登録者IDは整数で入力してください。',
            'user_id.exists' => 'そのIDは登録されていません。',

        ];
    }
}
