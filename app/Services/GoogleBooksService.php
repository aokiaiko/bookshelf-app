<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleBooksService
{
    /**
     * ISBNを使用してGoogle Books APIから書籍情報を取得する。
     */
    public function search(string $isbn): array
    {
        $response = Http::timeout(10)->get(
            'https://www.googleapis.com/books/v1/volumes',
            ['q' => 'isbn:'.$isbn]
        );

        $response->throw();

        $data = $response->json();

        if (empty($data['items'])) {
            throw new RuntimeException(
                '該当する書籍が見つかりませんでした。',
                404
            );
        }

        $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

        return [
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $volumeInfo['authors'] ?? []),
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
            'published_date' => $volumeInfo['publishedDate'] ?? null,
        ];
    }
}
