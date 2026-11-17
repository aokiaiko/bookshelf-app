<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_title(): void
    {
        Book::factory()->create([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);

        Book::factory()->create([
            'title' => 'FACTFULNESS',
            'author' => 'ハンス・ロスリング',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '坊',
        ]));

        $response->assertStatus(200);
        $response->assertSee('坊っちゃん');
        $response->assertDontSee('火花');
        $response->assertDontSee('FACTFULNESS');

    }

    public function test_search_author(): void
    {
        Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);

        Book::factory()->create([
            'title' => 'FACTFULNESS',
            'author' => 'ハンス・ロスリング',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
        ]));

        $response->assertStatus(200);
        $response->assertSee('夏目漱石');
        $response->assertDontSee('又吉直樹');
        $response->assertDontSee('ハンス・ロスリング');

    }

    public function test_search_non_exists_keyword(): void
    {
        Book::factory()->create([
            'title' => 'こころ',
        ]);

        Book::factory()->create([
            'title' => '火花',
        ]);

        Book::factory()->create([
            'title' => '坊っちゃん',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '存在しないキーワード',
        ]));

        $response->assertStatus(200);
        $response->assertSee('書籍が見つかりませんでした。');
        $response->assertDontSee('こころ');
        $response->assertDontSee('火花');
        $response->assertDontSee('坊っちゃん');
    }

    public function test_search_null_keyword(): void
    {
        Book::factory()->create([
            'title' => 'こころ',
        ]);

        Book::factory()->create([
            'title' => '火花',
        ]);

        Book::factory()->create([
            'title' => '坊っちゃん',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '',
        ]));

        $response->assertStatus(200);
        $response->assertSee('こころ');
        $response->assertSee('火花');
        $response->assertSee('坊っちゃん');
    }

    public function test_search_genre(): void
    {
        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $genre3 = Genre::factory()->create([
            'name' => 'ジャンル3',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
        ]);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);
        $book2->genres()->attach($genre2->id);

        $book3 = Book::factory()->create([
            'title' => 'FACTFULNESS',
            'author' => 'ハンス・ロスリング',
        ]);
        $book3->genres()->attach($genre3->id);

        $response = $this->get(route('books.index', [
            'genre' => $genre1->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('夏目漱石');
        $response->assertDontSee('又吉直樹');
        $response->assertDontSee('ハンス・ロスリング');

    }

    public function test_search_genre_without_book(): void
    {
        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $genre3 = Genre::factory()->create([
            'name' => 'ジャンル3',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
        ]);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);
        $book2->genres()->attach($genre2->id);

        $response = $this->get(route('books.index', [
            'genre' => $genre3->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('書籍が見つかりませんでした。');
        $response->assertDontSee('こころ');
        $response->assertDontSee('火花');

    }

    public function test_search_book_with_multiple_genres(): void
    {
        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $genre3 = Genre::factory()->create([
            'name' => 'ジャンル3',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
        ]);
        $book1->genres()->attach([
            $genre1->id,
            $genre2->id,
        ]);

        $book2 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);
        $book2->genres()->attach($genre3->id);

        $response = $this->get(route('books.index', [
            'genre' => $genre2->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('こころ');
        $response->assertDontSee('火花');
    }

    public function test_search_null_genre(): void
    {
        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
        ]);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);
        $book2->genres()->attach($genre2->id);

        $response = $this->get(route('books.index', [
            'genre' => '',
        ]));

        $response->assertStatus(200);
        $response->assertSee('こころ');
        $response->assertSee('火花');
    }

    public function test_search_keyword_and_genre(): void
    {
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
        ]);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
        ]);
        $book2->genres()->attach($genre2->id);

        $book3 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);
        $book3->genres()->attach($genre1->id);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
            'genre' => $genre1->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('こころ');
        $response->assertDontSee('坊っちゃん');
        $response->assertDontSee('火花');
    }

    public function test_search_keyword_and_sort(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
            'created_at' => now()->subDays(2),
        ]);

        $book2 = Book::factory()->create([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
            'created_at' => now()->subDay(),
        ]);

        $book3 = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'created_at' => now(),
        ]);

        $book4 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
            'created_at' => now()->addDay(),
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
            'sort' => 'newest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book3->title,
            $book2->title,
            $book1->title,
        ]);
        $response->assertDontSee($book4->title);
    }

    public function test_search_genre_and_sort(): void
    {
        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
            'created_at' => now()->subDays(2),
        ]);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'created_at' => now()->subDay(),
        ]);
        $book2->genres()->attach($genre1->id);

        $book3 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
            'created_at' => now(),
        ]);
        $book3->genres()->attach($genre2->id);

        $response = $this->get(route('books.index', [
            'genre' => $genre1->id,
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book1->title,
            $book2->title,
        ]);

        $response->assertDontSee($book3->title);
    }

    public function test_search_keyword_genre_and_sort(): void
    {
        $genre1 = Genre::factory()->create([
            'name' => 'ジャンル1',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => 'ジャンル2',
        ]);

        $book1 = Book::factory()->create([
            'title' => 'こころ',
            'author' => '夏目漱石',
            'created_at' => now()->subDays(2),
        ]);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'created_at' => now()->subDay(),
        ]);
        $book2->genres()->attach($genre1->id);

        $book3 = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
            'created_at' => now(),
        ]);
        $book3->genres()->attach($genre2->id);

        $book4 = Book::factory()->create([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
        ]);
        $book4->genres()->attach($genre2->id);

        $book5 = Book::factory()->create([
            'title' => '羅生門',
            'author' => '芥川龍之介',
        ]);
        $book5->genres()->attach($genre1->id);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
            'genre' => $genre1->id,
            'sort' => 'newest',
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $book2->title,
            $book1->title,
        ]);

        $response->assertDontSee($book3->title);
        $response->assertDontSee($book4->title);
        $response->assertDontSee($book5->title);
    }

    public function test_search_pagination(): void
    {
        $books = Book::factory()->count(11)->create([
            'author' => '夏目漱石',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11
                && $books->perPage() === 10;
        });

        $response->assertDontSee($otherBook->title);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1
                && $books->total() === 11
                && $books->currentPage() === 2;
        });

        $response->assertDontSee($otherBook->title);
    }

    public function test_search_conditions_are_kept_on_pagination(): void
    {
        $genre = Genre::factory()->create();

        $books = Book::factory()->count(11)->create([
            'author' => '夏目漱石',
        ]);

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $otherBook = Book::factory()->create([
            'title' => '火花',
            'author' => '又吉直樹',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '夏',
            'genre' => $genre->id,
            'sort' => 'oldest',
        ]));

        $response->assertStatus(200);

        $pageBooks = $response->viewData('books');

        $this->assertEquals(11, $pageBooks->total());
        $this->assertEquals(10, $pageBooks->count());

        $page2Url = $pageBooks->url(2);

        $this->assertStringContainsString('keyword='.urlencode('夏'), $page2Url);
        $this->assertStringContainsString('genre='.$genre->id, $page2Url);
        $this->assertStringContainsString('sort=oldest', $page2Url);
        $this->assertStringContainsString('page=2', $page2Url);
    }
}
