<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_page_displays_books_in_descending_order(): void
    {
        $bookA = Book::factory()->create([
            'title' => '書籍A',
            'image_url' => 'https://example.com/a.jpg',
        ]);
        $bookB = Book::factory()->create([
            'title' => '書籍B',
            'image_url' => 'https://example.com/b.jpg', ]);
        $bookC = Book::factory()->create([
            'title' => '書籍C',
            'image_url' => 'https://example.com/c.jpg',
        ]);

        Review::factory()->create(['book_id' => $bookA->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $bookA->id, 'rating' => 5]);

        Review::factory()->create(['book_id' => $bookB->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $bookB->id, 'rating' => 3]);

        Review::factory()->create(['book_id' => $bookC->id, 'rating' => 3]);
        Review::factory()->create(['book_id' => $bookC->id, 'rating' => 1]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertSeeInOrder(['書籍A', '書籍B', '書籍C']);

        foreach ([$bookA, $bookB, $bookC] as $book) {
            $response->assertSee($book->author);
            $response->assertSee('src="'.$book->image_url.'"', false);
        }

        $response->assertSee('5.00');
        $response->assertSee('4.00');
        $response->assertSee('2.00');
        $response->assertSee('(2件のレビュー)');
    }

    public function test_ranking_page_displays_only_top_10_books(): void
    {
        $books = Book::factory()->count(11)->create();

        foreach ($books as $index => $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => $index === 10 ? 1 : 5,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertDontSee($books[10]->title);
    }

    public function test_book_without_reviews_is_not_displayed_in_ranking(): void
    {
        $book = Book::factory()->create([
            'title' => 'レビューなしの書籍',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertDontSee($book->title);
    }

    public function test_ranking_page_has_link_to_book_show(): void
    {
        $book = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertSee('href="'.route('books.show', $book).'"', false);
    }
}
