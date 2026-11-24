<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_returns_books_with_genres_and_review_information(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'published_date',
                        'description',
                        'image_url',
                        'genres' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                        'reviews_avg_rating',
                        'reviews_count',
                    ],
                ],
            ]);

        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => $book->title,
            'reviews_avg_rating' => 4.5,
            'reviews_count' => 2,
        ]);
    }

    public function test_books_index_returns_empty_list(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_books_index_returns_paginated_books(): void
    {
        Book::factory()->count(21)->create();

        $response = $this->getJson('/api/v1/books?page=1');

        $response->assertOk()
            ->assertJsonCount(20, 'data');

        $response = $this->getJson('/api/v1/books?page=2');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_books_index_returns_correct_genres(): void
    {
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        $bookA->genres()->attach($genreA->id);
        $bookB->genres()->attach($genreB->id);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $bookA->id,
                'genres' => [
                    [
                        'id' => $genreA->id,
                        'name' => $genreA->name,
                    ],
                ],
            ])
            ->assertJsonFragment([
                'id' => $bookB->id,
                'genres' => [
                    [
                        'id' => $genreB->id,
                        'name' => $genreB->name,
                    ],
                ],
            ]);
    }

    public function test_books_index_can_search_by_keyword(): void
    {
        $bookA = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $bookB = Book::factory()->create([
            'title' => 'PHP入門',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $bookA->id,
                'title' => $bookA->title,
            ])
            ->assertJsonMissing([
                'id' => $bookB->id,
                'title' => $bookB->title,
            ]);
    }

    public function test_books_index_rejects_non_string_keyword(): void
    {
        $response = $this->getJson('/api/v1/books?'.http_build_query([
            'keyword' => ['Laravel'],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'keyword' => 'キーワードは文字列で入力してください。',
            ]);
    }

    public function test_books_index_rejects_keyword_over_255_characters(): void
    {
        $response = $this->getJson('/api/v1/books?'.http_build_query([
            'keyword' => str_repeat('a', 256),
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'keyword' => 'キーワードは255文字以下で入力してください。',
            ]);
    }

    public function test_books_index_can_filter_by_genre(): void
    {
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        $bookA->genres()->attach($genreA->id);
        $bookB->genres()->attach($genreB->id);

        $response = $this->getJson('/api/v1/books?genre_id='.$genreA->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $bookA->id,
                'title' => $bookA->title,
            ])
            ->assertJsonMissing([
                'id' => $bookB->id,
                'title' => $bookB->title,
            ]);
    }

    public function test_books_index_rejects_non_integer_genre_id(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id' => 'ジャンルIDは整数で入力してください。',
            ]);
    }

    public function test_books_index_rejects_nonexistent_genre_id(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id' => 'そのジャンルIDは存在しません。',
            ]);
    }

    public function test_books_index_rejects_non_integer_page(): void
    {
        $response = $this->getJson('/api/v1/books?page=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'page' => 'ページ番号は整数で入力してください。',
            ]);
    }

    public function test_books_index_rejects_page_less_than_one(): void
    {
        $response = $this->getJson('/api/v1/books?page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'page' => 'ページ番号は1以上の整数で入力してください。',
            ]);
    }

    public function test_books_index_rejects_non_integer_per_page(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page' => '1ページあたりの件数は整数で入力してください。',
            ]);
    }

    public function test_books_index_rejects_per_page_less_than_one(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page' => '1ページあたりの件数は1以上で入力してください。',
            ]);
    }

    public function test_books_index_rejects_per_page_greater_than_100(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page' => '1ページあたりの件数は100以下で入力してください。',
            ]);
    }

    public function test_books_show_returns_book_with_genres_and_reviews(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '読みやすい本でした。',
        ]);

        $response = $this->getJson('/api/v1/books/'.$book->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_url',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'reviews' => [
                        '*' => [
                            'id',
                            'name',
                            'rating',
                            'comment',
                            'created_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn,
                'published_date' => $book->published_date,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => [
                    [
                        'id' => $genre->id,
                        'name' => $genre->name,
                    ],
                ],
                'reviews' => [
                    [
                        'id' => $review->id,
                        'name' => $review->user->name,
                        'rating' => 4,
                        'comment' => '読みやすい本でした。',
                        'created_at' => $review->created_at->toISOString(),
                    ],
                ],
            ]);
    }

    public function test_books_show_returns_404_for_nonexistent_book(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertNotFound();
    }

    public function test_guest_cannot_store_book(): void
    {

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_books_store_creates_book(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01T00:00:00.000000Z',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertCreated()
            ->assertJsonFragment([
                'title' => $data['title'],
                'author' => $data['author'],
                'isbn' => $data['isbn'],
                'published_date' => $data['published_date'],
                'description' => $data['description'],
                'image_url' => $data['image_url'],
            ]);

        $book = Book::where('isbn', $data['isbn'])->firstOrFail();

        $this->assertDatabaseHas('books', [
            'isbn' => $data['isbn'],
            'user_id' => $user->id,
            'published_date' => '2026-01-01',
        ]);

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_books_store_requires_title(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは必須です。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_requires_non_string_title(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => ['テスト書籍'],
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは文字列で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_title_longer_than_255(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => str_repeat('a', 256),
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは255文字以内で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_requires_author(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author' => '著者名は必須です。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_non_string_author(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => ['テスト著者'],
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author' => '著者名は文字列で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_author_longer_than_255(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => str_repeat('a', 256),
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author' => '著者名は255文字以内で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_requires_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは必須です。',
            ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_books_store_rejects_non_string_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => ['9781234567890'],
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは文字列で入力してください。',
            ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_books_store_rejects_isbn_not_13_characters(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '978123456789',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは13桁で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'isbn' => '9781234567890',
        ]);

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'そのISBNは既に使用されています。',
            ]);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_books_store_requires_genres(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_invalid_image_url(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'invalid-url',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url' => '画像URLは有効なURL形式で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_image_url_longer_than_255(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/'.str_repeat('a', 237),
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url' => '画像URLは255文字以内で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_invalid_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => 'invalid-date',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'published_date' => '出版日は有効な日付形式で入力してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_non_array_genres(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => '1',
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_rejects_empty_genres(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => $data['isbn'],
        ]);
    }

    public function test_books_store_allows_nullable_fields(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'isbn' => '9781234567890',
            'description' => null,
            'image_url' => null,
        ]);
    }

    public function test_creation_is_rolled_back_when_genre_sync_fails(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'genres' => [9999],
        ];

        Sanctum::actingAs($user);

        $this->withoutExceptionHandling();

        try {
            $this->postJson('/api/v1/books', $data);

            $this->fail('例外が発生しませんでした。');
        } catch (QueryException $e) {
            $this->assertDatabaseMissing('books', [
                'isbn' => '1234567890123',
            ]);
        }
    }

    public function test_books_update_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/99999', []);

        $response->assertNotFound();
    }

    public function test_guest_cannot_update_book(): void
    {
        $book = Book::factory()->create();

        $data = [
            'title' => '更新後のテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
        ];

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertStatus(401);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_another_user_cannot_update_book(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $userA->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($userB);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_updates_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book->genres()->attach($oldGenre->id);

        $updateData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01T00:00:00.000000Z',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$newGenre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            $updateData
        );

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $book->id,
                'title' => $updateData['title'],
                'author' => $updateData['author'],
                'isbn' => $updateData['isbn'],
                'published_date' => $updateData['published_date'],
                'description' => $updateData['description'],
                'image_url' => $updateData['image_url'],
                'genres' => [
                    [
                        'id' => $newGenre->id,
                        'name' => $newGenre->name,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $updateData['title'],
            'author' => $updateData['author'],
            'isbn' => $updateData['isbn'],
            'published_date' => '2026-01-01',
            'description' => $updateData['description'],
            'image_url' => $updateData['image_url'],
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_books_update_requires_title(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $data = [
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは必須です。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $book->user_id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_books_update_requires_non_string_title(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => ['更新後のタイトル'],
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは文字列で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_rejects_title_longer_than_255(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => str_repeat('あ', 256),
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'タイトルは255文字以内で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_requires_author(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author' => '著者名は必須です。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_rejects_non_string_author(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => ['更新後の著者'],
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author' => '著者名は文字列で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_rejects_author_longer_than_255(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => str_repeat('あ', 256),
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author' => '著者名は255文字以内で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_requires_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは必須です。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_rejects_non_string_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => ['9781234567890'],
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは文字列で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_rejects_isbn_not_13_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '978123456789',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'ISBNは13桁で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_books_update_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $bookA = Book::factory()->create([
            'isbn' => '9781234567890',
        ]);

        $bookB = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567891',
        ]);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => $bookA->isbn,
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$bookB->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn' => 'そのISBNは既に使用されています。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $bookB->id,
            'isbn' => '9781234567891',
        ]);
    }

    public function test_books_update_requires_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_books_update_rejects_empty_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_books_update_rejects_invalid_image_url(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'invalid-url',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url' => '画像URLは有効なURL形式で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'image_url' => $book->image_url,
        ]);
    }

    public function test_books_update_rejects_image_url_over_255_characters(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/'.str_repeat('a', 237),
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url' => '画像URLは255文字以内で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'image_url' => $book->image_url,
        ]);
    }

    public function test_books_update_rejects_invalid_published_date(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => 'invalid-date',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'published_date' => '出版日は有効な日付形式で入力してください。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
        ]);
    }

    public function test_books_update_rejects_non_array_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => '1',
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['genres']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_books_update_allows_unchanged_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
            'title' => '更新前のタイトル',
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $book->id,
                'title' => $data['title'],
                'isbn' => $book->isbn,
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_books_update_allows_nullable_fields(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'published_date' => '2026-11-01',
            'description' => '更新前の説明です。',
            'image_url' => 'https://example.com/old-book.jpg',
        ]);

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/'.$book->id, $data);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'description' => null,
            'image_url' => null,
        ]);
    }

    public function test_update_is_rolled_back_when_genre_sync_fails(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前の書籍',
        ]);

        $book->genres()->attach($genre->id);

        $data = [
            'title' => '更新後の書籍',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'genres' => [9999],
        ];

        Sanctum::actingAs($user);

        $this->withoutExceptionHandling();

        try {
            $response = $this->putJson('/api/v1/books/'.$book->id, $data);

            $this->fail('例外が発生しませんでした。');
        } catch (QueryException $e) {
            $this->assertDatabaseHas('books', [
                'id' => $book->id,
                'title' => '更新前の書籍',
            ]);

            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_another_user_cannot_delete_book(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $userA->id,
        ]);

        Sanctum::actingAs($userB);

        $response = $this->deleteJson('/api/v1/books/'.$book->id);
        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_books_destroy_deletes_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $user->likedReviews()->attach($review->id);

        $user->favoriteBooks()->attach($book->id);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_books_destroy_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/99999');

        $response->assertNotFound();
    }
}
