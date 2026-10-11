<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class GenreTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $genre = new Genre;

        $genre->fill([
            'name' => 'テストジャンル',
        ]);

        $this->assertEquals('テストジャンル', $genre->name);
    }

    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $genre = new Genre;

        $genre->fill([
            'admin_flag' => true,
        ]);

        $this->assertArrayNotHasKey('admin_flag', $genre->getAttributes());
    }

    public function test_genre_belongs_to_many_books(): void
    {
        $genre = new Genre;

        $relation = $genre->books();

        $this->assertInstanceOf(
            BelongsToMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Book::class,
            $relation->getRelated()
        );

        $this->assertEquals(
            'book_genres',
            $relation->getTable()
        );
    }
}
