<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_sort_is_newest(): void
    {

        $book1 = Book::factory()->create([
            'title' => 'テスト書籍1',
            'created_at' => now()->subDays(2),
        ]);

        $book2 = Book::factory()->create([
            'title' => 'テスト書籍2',
            'created_at' => now()->subDay(),
        ]);

        $book3 = Book::factory()->create([
            'title' => 'テスト書籍3',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book3->title,
            $book2->title,
            $book1->title,
        ]);
    }

    public function test_sort_is_newest(): void
    {

        $book1 = Book::factory()->create([
            'title' => 'テスト書籍1',
            'created_at' => now()->subDays(2),
        ]);

        $book2 = Book::factory()->create([
            'title' => 'テスト書籍2',
            'created_at' => now()->subDay(),
        ]);

        $book3 = Book::factory()->create([
            'title' => 'テスト書籍3',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book3->title,
            $book2->title,
            $book1->title,
        ]);
    }

    public function test_sort_is_oldest(): void
    {

        $book1 = Book::factory()->create([
            'title' => 'テスト書籍1',
            'created_at' => now()->subDays(2),
        ]);

        $book2 = Book::factory()->create([
            'title' => 'テスト書籍2',
            'created_at' => now()->subDay(),
        ]);

        $book3 = Book::factory()->create([
            'title' => 'テスト書籍3',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book1->title,
            $book2->title,
            $book3->title,
        ]);
    }

    public function test_sort_is_title(): void
    {

        $book1 = Book::factory()->create([
            'title' => 'Book C',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'Book A',
        ]);

        $book3 = Book::factory()->create([
            'title' => 'Book B',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book2->title,
            $book3->title,
            $book1->title,
        ]);
    }

    public function test_sort_by_average_rating(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'Book A',
        ]);
        Review::factory()->create([
            'book_id' => $book1->id,
            'rating' => 3,
        ]);
        Review::factory()->create([
            'book_id' => $book1->id,
            'rating' => 2,
        ]);

        $book2 = Book::factory()->create([
            'title' => 'Book B',
        ]);
        Review::factory()->create([
            'book_id' => $book2->id,
            'rating' => 4,
        ]);
        Review::factory()->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book2->title,
            $book1->title,
        ]);
    }

    public function test_sort_is_newest_when_created_at_is_same(): void
    {
        $date = now();

        $book1 = Book::factory()->create([
            'title' => 'テスト書籍1',
            'created_at' => $date,
        ]);

        $book2 = Book::factory()->create([
            'title' => 'テスト書籍2',
            'created_at' => $date,
        ]);

        $book3 = Book::factory()->create([
            'title' => 'テスト書籍3',
            'created_at' => $date,
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book3->title,
            $book2->title,
            $book1->title,
        ]);
    }

    public function test_sort_is_oldest_when_created_at_is_same(): void
    {
        $date = now();

        $book1 = Book::factory()->create([
            'title' => 'テスト書籍1',
            'created_at' => $date,
        ]);

        $book2 = Book::factory()->create([
            'title' => 'テスト書籍2',
            'created_at' => $date,
        ]);

        $book3 = Book::factory()->create([
            'title' => 'テスト書籍3',
            'created_at' => $date,
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book1->title,
            $book2->title,
            $book3->title,
        ]);
    }

    public function test_sort_book_non_rating(): void
    {
        $book3 = Book::factory()->create([
            'title' => 'Book C',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'Book A',
        ]);
        Review::factory()->count(3)->create([
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        $book2 = Book::factory()->create([
            'title' => 'Book B',
        ]);
        Review::factory()->count(2)->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book2->title,
            $book1->title,
            $book3->title,
        ]);
    }

    public function test_sort_invalid(): void
    {

        $book1 = Book::factory()->create([
            'title' => 'テスト書籍1',
            'created_at' => now()->subDays(2),
        ]);

        $book2 = Book::factory()->create([
            'title' => 'テスト書籍2',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'invalid',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book2->title,
            $book1->title,
        ]);
    }
}
