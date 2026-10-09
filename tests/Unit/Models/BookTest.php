<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $book = new Book;

        $book->fill([
            'user_id' => 1,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-10-09',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $this->assertEquals(1, $book->user_id);
        $this->assertEquals('テスト書籍', $book->title);
        $this->assertEquals('テスト著者', $book->author);
        $this->assertEquals('1234567890123', $book->isbn);
        $this->assertEquals('2026-10-09', $book->published_date->format('Y-m-d'));
        $this->assertEquals('テスト説明', $book->description);
        $this->assertEquals('https://example.com/book.jpg', $book->image_url);
    }

    public function test_unfillable_attributes_cannot_be_mass_assigned(): void
    {
        $book = new Book;

        $book->fill([
            'title' => 'テスト書籍',
            'admin_flag' => true,
        ]);

        $this->assertEquals('テスト書籍', $book->title);
        $this->assertNull($book->getAttribute('admin_flag'));
    }

    public function test_published_date_is_cast_to_date(): void
    {
        $book = new Book;

        $book->published_date = '2026-10-09';

        $this->assertInstanceOf(
            Carbon::class,
            $book->published_date
        );
    }

    public function test_book_belongs_to_user(): void
    {
        $book = new Book;

        $relation = $book->user();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );
    }

    public function test_book_has_many_reviews(): void
    {
        $book = new Book;

        $relation = $book->reviews();

        $this->assertInstanceOf(
            HasMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Review::class,
            $relation->getRelated()
        );
    }

    public function test_book_belongs_to_many_favorite_users(): void
    {
        $book = new Book;

        $relation = $book->favoriteUsers();

        $this->assertInstanceOf(
            BelongsToMany::class,
            $relation
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );

        $this->assertEquals(
            'favorites',
            $relation->getTable()
        );
    }

    public function test_book_belongs_to_many_genres(): void
    {
        $book = new Book;

        $relation = $book->genres();

        $this->assertInstanceOf(
            BelongsToMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Genre::class,
            $relation->getRelated()
        );

        $this->assertEquals(
            'book_genres',
            $relation->getTable()
        );
    }
}
