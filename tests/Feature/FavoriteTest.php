<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_add_book_to_favorites(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }

    public function test_favorites_index_displays_books_with_pagination(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(11)->create();

        $user->favoriteBooks()->attach($books->pluck('id'));

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
             && $books->perPage() === 10;
        });

        $pageBooks = $response->viewData('books');

        foreach ($pageBooks as $book) {
            $response->assertSee([
                $book->title,
                $book->author,
                $book->isbn,
                $book->image_url,
            ]);
        }

        $response = $this->get(route('favorites.index', [
            'page' => 2,
        ]));

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });

        $pageBooks = $response->viewData('books');

        foreach ($pageBooks as $book) {
            $response->assertSee([
                $book->title,
                $book->author,
                $book->isbn,
                $book->image_url,
            ]);
        }
    }

    public function test_book_show_displays_favorite_icon(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('title="お気に入りに追加"', false);
    }

    public function test_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('class="text-red-500 hover:text-red-700"', false);
    }

    public function test_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('class="text-gray-400 hover:text-red-500"', false);
    }

    public function test_favorites_index_links_to_book_show(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee('href="'.route('books.show', $book).'"', false);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee($book->title);
    }

    public function test_favorites_index_displays_only_authenticated_users_books(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        $userA->favoriteBooks()->attach($bookA->id);
        $userB->favoriteBooks()->attach($bookB->id);

        $response = $this->actingAs($userA)->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertSee($bookA->title);
        $response->assertDontSee($bookB->title);
    }
}
