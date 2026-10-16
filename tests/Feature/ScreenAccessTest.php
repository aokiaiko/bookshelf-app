<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_books_index(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertStatus(200);
    }

    public function test_guest_can_access_book_show(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
    }

    public function test_guest_can_access_ranking_index(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_from_book_create(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_book_edit(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.edit', $book));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_favorites_index(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_genres_index(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_genre_show(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.show', $genre));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_genre_create(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_genre_edit(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_guest_is_redirected_to_login_from_review_edit(): void
    {
        $review = Review::factory()->create();

        $response = $this->get(route('reviews.edit', $review));

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    public function test_user_can_access_books_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('books.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_book_show(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertStatus(200);
    }

    public function test_user_can_access_book_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('books.create'));

        $response->assertStatus(200);
    }

    public function test_owner_can_access_book_edit(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('books.edit', $book));

        $response->assertStatus(200);
    }

    public function test_non_owner_can_not_access_book_edit(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userB)->get(route('books.edit', $book));

        $response->assertStatus(403);
    }

    public function test_user_can_access_favorites_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_genres_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_genre_show(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertStatus(200);
    }

    public function test_user_can_access_genre_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_genre_edit(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
    }

    public function test_user_can_access_ranking_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ranking.index'));

        $response->assertStatus(200);
    }

    public function test_owner_can_access_review_edit(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reviews.edit', $review));

        $response->assertStatus(200);
    }

    public function test_non_owner_can_not_access_review_edit(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userB)->get(route('reviews.edit', $review));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_nonexistent_book_show(): void
    {
        $nonexistentId = Book::max('id') + 1;

        $response = $this->get(route('books.show', $nonexistentId));

        $response->assertStatus(404);
    }

    public function test_user_cannot_access_nonexistent_book_edit(): void
    {
        $user = User::factory()->create();
        $nonexistentId = Book::max('id') + 1;

        $response = $this->actingAs($user)->get(route('books.edit', $nonexistentId));

        $response->assertStatus(404);
    }

    public function test_user_cannot_access_nonexistent_review_edit(): void
    {
        $user = User::factory()->create();
        $nonexistentId = Review::max('id') + 1;

        $response = $this->actingAs($user)->get(route('reviews.edit', $nonexistentId));

        $response->assertStatus(404);
    }

    public function test_user_cannot_access_nonexistent_genre_show(): void
    {
        $user = User::factory()->create();
        $nonexistentId = Genre::max('id') + 1;

        $response = $this->actingAs($user)->get(route('genres.show', $nonexistentId));

        $response->assertStatus(404);
    }

    public function test_user_cannot_access_nonexistent_genre_edit(): void
    {
        $user = User::factory()->create();
        $nonexistentId = Genre::max('id') + 1;

        $response = $this->actingAs($user)->get(route('genres.edit', $nonexistentId));

        $response->assertStatus(404);
    }
}
