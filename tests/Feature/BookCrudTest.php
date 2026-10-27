<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create([
            'image_url' => 'https://example.com/book.jpg',
        ]);

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->get(route('books.index'));

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });

        $pageBooks = $response->viewData('books');

        foreach ($pageBooks as $book) {
            $response->assertSee($book->title);
            $response->assertSee($book->author);
            $response->assertSee($book->image_url);

            foreach ($book->genres as $genre) {
                $response->assertSee($genre->name);
            }
        }

        $response->assertSee('書籍を登録');

        $response = $this->get(route('books.index', ['page' => 2]));

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });

        $pageBooks = $response->viewData('books');

        foreach ($pageBooks as $book) {
            $response->assertSee($book->title);
            $response->assertSee($book->author);
            $response->assertSee($book->image_url);

            foreach ($book->genres as $genre) {
                $response->assertSee($genre->name);
            }
        }

        $response->assertSee('書籍を登録');
    }

    public function test_book_detail_page_displays_book_information(): void
    {
        $book = Book::factory()->create([
            'image_url' => 'https://example.com/book.jpg',
        ]);
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $user = User::factory()->create();
        $review->likedByUsers()->attach($user->id);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);

        $response->assertSee($book->title);
        $response->assertSee($book->author);
        $response->assertSee($book->isbn);
        $response->assertSee($book->published_date);
        $response->assertSee($book->description);
        $response->assertSee($book->image_url);

        $response->assertSee($genre->name);

        $response->assertSee($review->comment);
        $response->assertSee((string) $review->rating);

        $response->assertSee('1');
    }

    public function test_book_detail_displays_no_image_when_image_url_is_null(): void
    {
        $book = Book::factory()->create([
            'image_url' => null,
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('画像なし');
    }

    public function test_book_create_page_displays_form(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(3)->create();
        $response = $this->actingAs($user)->get(route('books.create'));

        $response->assertStatus(200);

        foreach ($genres as $genre) {
            $response->assertSee(
                'type="checkbox" name="genres[]" value="'.$genre->id.'"',
                false
            );

            $response->assertSee($genre->name);
        }
        $response->assertSee('キャンセル');
        $response->assertSee('登録');
    }

     public function test_book_edit_page_displays_form(): void
{
    $user = User::factory()->create();
    $genres = Genre::factory()->count(3)->create();

    $book = Book::factory()->create([
        'user_id' => $user->id,
        'title' => 'テスト書籍',
        'author' => 'テスト著者',
        'isbn' => '1234567890123',
        'published_date' => '2026-10-01',
        'description' => 'テスト説明',
        'image_url' => 'https://example.com/book.jpg',
    ]);

    $book->genres()->attach($genres[0]->id);

    $response = $this->actingAs($user)
        ->get(route('books.edit', $book));

    $response->assertStatus(200);

    $response->assertSee('value="'.$book->title.'"', false);
    $response->assertSee('value="'.$book->author.'"', false);
    $response->assertSee('value="'.$book->isbn.'"', false);
    $response->assertSee('value="'.$book->published_date.'"', false);
    $response->assertSee($book->description);
    $response->assertSee('value="'.$book->image_url.'"', false);

    foreach ($genres as $genre) {
        $response->assertSee($genre->name);
    }

    $response->assertSee('キャンセル');
    $response->assertSee('更新');
}

    public function test_guest_cannot_store_book(): void
    {

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
        ];

        $response = $this->post(route('books.store'), $data);

        $response->assertStatus(302);
        $response->assertRedirect('login');

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_title_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => '',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'title' => 'タイトルは必須です。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_title_is_not_strings(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => ['テスト本'],
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'title' => 'タイトルは文字列で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_title_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => str_repeat('あ', 256),
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'title' => 'タイトルは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_author_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => '',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'author' => '著者名は必須です。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_author_is_not_strings(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => ['テスト著者'],
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'author' => '著者名は文字列で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_author_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => str_repeat('あ', 256),
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'author' => '著者名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_isbn_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは必須です。',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_isbn_is_not_strings(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => ['1234567890123'],
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは文字列で入力してください。',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_isbn_is_not_13_characters(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '12345678901234',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁で入力してください。',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_isbn_is_unique(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-02',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'そのISBNは既に使用されています。',
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_book_genres_are_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルは1つ以上選択してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_image_url_has_invalid_format(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
            'image_url' => '1111111',
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは有効なURL形式で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_image_url_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
            'image_url' => 'https://example.com/'.str_repeat('あ', 237),
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_published_date_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'published_date' => '出版日は必須です。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_published_date_has_invalid_format(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => 'invalid-date',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors([
            'published_date' => '出版日は有効な日付形式で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_create(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
        ]);

        $book = Book::where('isbn', '1234567890123')->firstOrFail();

        $this->assertDatabaseHas('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('books.show', $book));
    }

    public function test_book_can_see_book_show_and_displays_success_message(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $book = Book::where('isbn', '1234567890123')->firstOrFail();

        $response = $this->get(route('books.show', $book));
        $response->assertSee([
            'テスト本',
            'テスト著者',
            '1234567890123',
            '2026-01-01',
            $genre->name,
        ]);

        $response->assertSee('書籍を登録しました');
    }

    public function test_book_description_is_not_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
            'description' => '',
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $this->assertDatabaseHas('books', [
            'isbn' => '1234567890123',
            'description' => null,
        ]);
    }

    public function test_book_image_url_is_not_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
            'image_url' => '',
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $this->assertDatabaseHas('books', [
            'isbn' => '1234567890123',
            'image_url' => null,
        ]);
    }

    public function test_book_genres_must_be_array(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => 'テストジャンル',
        ];

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('genres');

        $this->assertDatabaseMissing('books', [
            'isbn' => '1234567890123',
        ]);
    }

    public function test_book_create_preserves_input_after_validation_error(): void
{
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $data = [
        'title' => str_repeat('あ', 256),
        'author' => 'テスト著者',
        'isbn' => '1234567890123',
        'published_date' => '2026-10-01',
        'description' => 'テスト説明',
        'image_url' => 'https://example.com/book.jpg',
        'genres' => [$genre->id],
    ];

    $response = $this->actingAs($user)
        ->post(route('books.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['title']);

    $response->assertSessionHasInput('title', $data['title']);
    $response->assertSessionHasInput('author', $data['author']);
    $response->assertSessionHasInput('isbn', $data['isbn']);
    $response->assertSessionHasInput('published_date', $data['published_date']);
    $response->assertSessionHasInput('genres', $data['genres']);
    $response->assertSessionHasInput('description', $data['description']);
    $response->assertSessionHasInput('image_url', $data['image_url']);
   
$response = $this->get(route('books.create'));

$response->assertStatus(200);

$response->assertSee('value="'.$data['title'].'"', false);
$response->assertSee('value="'.$data['author'].'"', false);
$response->assertSee('value="'.$data['isbn'].'"', false);
$response->assertSee('value="'.$data['published_date'].'"', false);
$response->assertSee(
    'checked',
    false
);
$response->assertSee($data['description']);
$response->assertSee('value="'.$data['image_url'].'"', false);

$response->assertSee('タイトルは255文字以内で入力してください。');
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

        $response = $this->put(route('books.update', $book), $data);

        $response->assertStatus(302);
        $response->assertRedirect('login');

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

        $response = $this->actingAs($userB)->put(route('books.update', $book), $data);

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_book_owner_can_see_edit_button(): void
{
    $user = User::factory()->create();

    $book = Book::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('books.show', $book));

    $response->assertStatus(200);

    $response->assertSee('編集');
    $response->assertSee(route('books.edit', $book));
}

public function test_non_owner_cannot_see_edit_button(): void
{
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $book = Book::factory()->create([
        'user_id' => $userA->id,
    ]);

    $response = $this->actingAs($userB)
        ->get(route('books.show', $book));

    $response->assertStatus(200);

    $response->assertDontSee('編集');
    $response->assertDontSee(route('books.edit', $book));
}

    #[DataProvider('invalidBookData')]
    public function test_book_update_validation(
        string $field,
        mixed $value,
        string $message
    ): void {
        $user = User::factory()->create();
        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genreA->id);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'genres' => [$genreB->id],
            'image_url' => 'https://example.com/',
        ];

        $data[$field] = $value;

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertSessionHasErrors([
            $field => $message,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'image_url' => $book->image_url,
        ]);

        $this->assertDatabaseHas('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genreA->id,
        ]);

        $this->assertDatabaseMissing('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genreB->id,
        ]);
    }

    public static function invalidBookData(): array
    {
        return [
            'タイトル未入力' => ['title', '', 'タイトルは必須です。'],
            'タイトル文字列以外' => ['title', ['テスト本'], 'タイトルは文字列で入力してください。'],
            'タイトル256文字' => ['title', str_repeat('あ', 256), 'タイトルは255文字以内で入力してください。'],

            '著者名未入力' => ['author', '', '著者名は必須です。'],
            '著者名文字列以外' => ['author', ['テスト著者'], '著者名は文字列で入力してください。'],
            '著者名256文字' => ['author', str_repeat('あ', 256), '著者名は255文字以内で入力してください。'],

            'ISBN未入力' => ['isbn', '', 'ISBNは必須です。'],
            'ISBN文字列以外' => ['isbn', ['isbn'], 'ISBNは文字列で入力してください。'],
            'ISBN13桁以外' => ['isbn', '12345678901234', 'ISBNは13桁で入力してください。'],

            'ジャンル未入力' => ['genres', [], 'ジャンルは1つ以上選択してください。'],

            '画像URL有効なURL以外' => ['image_url',  '111111', '画像URLは有効なURL形式で入力してください。'],
            '画像URL256文字' => ['image_url', 'https://example.com/'.str_repeat('a', 236), '画像URLは255文字以内で入力してください。'],

            '出版日未入力' => ['published_date', '', '出版日は必須です。'],
            '出版日有効な日付以外' => ['published_date',  'invalid-date', '出版日は有効な日付形式で入力してください。'],

        ];
    }

    public function test_book_update(): void
    {
        $user = User::factory()->create();
        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genreA->id);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genreB->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
        ]);

        $this->assertDatabaseHas('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genreB->id,
        ]);

        $this->assertDatabaseMissing('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genreA->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_book_update_book_show_and_displays_success_message(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(route('books.show', $book));

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テスト本');
        $response->assertSee('テスト著者');
        $response->assertSee('1234567890123');
        $response->assertSee('2026-01-01');
        $response->assertSee($genre->name);

        $response->assertSee('書籍情報を更新しました。');
    }

    public function test_update_book_description_is_not_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'description' => '更新前の説明',
        ]);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
            'description' => '',
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'description' => null,
        ]);
    }

    public function test_update_book_image_url_is_not_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
            'image_url' => '',
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'image_url' => null,
        ]);
    }

    public function test_update_book_genres_must_be_array(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => 'テストジャンル',
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('genres');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
        ]);

        $this->assertDatabaseHas('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_update_book_isbn_is_unique(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherBook = Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => $otherBook->isbn,
            'published_date' => '2026-01-02',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertSessionHasErrors([
            'isbn' => 'そのISBNは既に使用されています。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => $book->isbn,
        ]);
    }

    public function test_update_book_except_own_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'テスト本',
            'author' => 'テスト著者',
            'isbn' => $book->isbn,
        ]);
    }

   public function test_book_update_preserves_input_after_validation_error(): void
{
    $user = User::factory()->create();
    $genre = Genre::factory()->create();

    $book = Book::factory()->create([
        'user_id' => $user->id,
    ]);

    $book->genres()->attach($genre->id);

    $data = [
        'title' => str_repeat('あ', 256),
        'author' => '更新後の著者',
        'isbn' => $book->isbn,
        'published_date' => '2026-10-01',
        'description' => '更新後の説明',
        'image_url' => 'https://example.com/book.jpg',
        'genres' => [$genre->id],
    ];

    $response = $this->actingAs($user)
        ->put(route('books.update', $book), $data);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['title']);

    $response->assertSessionHasInput('title', $data['title']);
    $response->assertSessionHasInput('author', $data['author']);
    $response->assertSessionHasInput('isbn', $data['isbn']);
    $response->assertSessionHasInput('published_date', $data['published_date']);
    $response->assertSessionHasInput('description', $data['description']);
    $response->assertSessionHasInput('image_url', $data['image_url']);
    $response->assertSessionHasInput('genres', $data['genres']);

    $response = $this->get(route('books.edit', $book));

    $response->assertStatus(200);

    $response->assertSee('value="'.$data['title'].'"', false);
    $response->assertSee('value="'.$data['author'].'"', false);
    $response->assertSee('value="'.$data['isbn'].'"', false);
    $response->assertSee('value="'.$data['published_date'].'"', false);
    $response->assertSee($data['description']);
    $response->assertSee('value="'.$data['image_url'].'"', false);

    $response->assertSee('タイトルは255文字以内で入力してください。');
}

    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect('login');

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

        $response = $this->actingAs($userB)->delete(route('books.destroy', $book));

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_book_owner_can_see_delete_button(): void
{
    $user = User::factory()->create();

    $book = Book::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('books.show', $book));

    $response->assertStatus(200);

    $response->assertSee('削除');
     $response->assertSee(
    'action="'.route('books.destroy', $book).'"',
    false
);
}

public function test_non_owner_cannot_see_delete_button(): void
{
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $book = Book::factory()->create([
        'user_id' => $userA->id,
    ]);

    $response = $this->actingAs($userB)
        ->get(route('books.show', $book));

    $response->assertStatus(200);

    $response->assertDontSee('削除');
    $response->assertDontSee(
    'action="'.route('books.destroy', $book).'"',
    false
);
}

    public function test_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_deleted_book_displays_success_message(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $response = $this->get(route('books.index'));

        $response->assertSee('書籍を削除しました。');
    }

    public function test_deleted_book_other_data_delete(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        Review::factory()->count(3)->create([
            'book_id' => $book->id,
        ]);

        $user->favoriteBooks()->attach($book->id);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genres', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,

        ]);
    }
}
