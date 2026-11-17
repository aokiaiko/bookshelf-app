<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search_book_by_isbn(): void
    {
        $response = $this->get('/books/isbn/9781234567890');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_search_book_by_valid_isbn(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['テスト著者'],
                            'description' => 'テスト説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                            'publishedDate' => '2026-01-01',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9781234567890');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'description' => 'テスト説明',
                'image_url' => 'https://example.com/book.jpg',
                'published_date' => '2026-01-01',
            ]);
    }

    public function test_search_book_with_short_isbn_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/isbn/978123456789');

        $response->assertStatus(422)
            ->assertJsonFragment([
                'ISBNは13桁で入力してください。',
            ]);
    }

    public function test_search_book_with_long_isbn_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/isbn/97812345678901');

        $response->assertStatus(422)
            ->assertJsonFragment([
                'ISBNは13桁で入力してください。',
            ]);
    }

    public function test_search_book_non_exists(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9781234567890');

        $response->assertStatus(404)
            ->assertJsonFragment([
                '書籍が見つかりませんでした。',
            ]);
    }

    public function test_search_book_connection_error(): void
    {
        $user = User::factory()->create();

        Http::fake(function () {
            throw new ConnectionException('通信エラー');
        });

        $response = $this->actingAs($user)->get('/books/isbn/9781234567890');

        $response->assertStatus(504)
            ->assertJsonFragment([
                'API通信エラーが発生しました。',
            ]);
    }

    public function test_search_book_error_by_too_many_request(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 429),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9781234567890');

        $response->assertStatus(429)
            ->assertJsonFragment([
                'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。',
            ]);
    }

    public function test_search_book_error_returns_502(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 502),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9781234567890');

        $response->assertStatus(502)
            ->assertJsonFragment([
                'API通信エラーが発生しました。',
            ]);
    }

    public function test_search_book_with_missing_optional_fields(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/books/isbn/9781234567890');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'テスト書籍',
                'author' => '',
                'description' => '',
                'image_url' => '',
                'published_date' => null,
            ]);
    }
}
