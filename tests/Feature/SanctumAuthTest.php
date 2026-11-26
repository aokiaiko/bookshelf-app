<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_store_book_without_token(): void
    {
        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567890',
        ]);
    }

    public function test_can_store_book_with_valid_token(): void
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

        $response->assertStatus(201)
            ->assertJsonFragment([
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
            ]);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_cannot_update_book_without_token(): void
    {
        $book = Book::factory()->create();

        $data = [
            'title' => '更新後の書籍',
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response->assertStatus(401);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
        ]);
    }

    public function test_owner_can_update_book_with_valid_token(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
            'published_date' => $book->published_date,
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => '更新後の書籍',
                'author' => '更新後の著者',
                'isbn' => '9781234567890',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_cannot_update_other_users_book(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $userB->id,
        ]);

        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($userA);

        $response = $this->putJson("/api/v1/books/{$book->id}", $data);

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $userB->id,
            'title' => $book->title,
        ]);
    }

    public function test_cannot_delete_book_without_token(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_owner_can_delete_book_with_valid_token(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_cannot_delete_other_users_book(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $userB->id,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $userB->id,
        ]);
    }
}
