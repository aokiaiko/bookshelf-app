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
}
