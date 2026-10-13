<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $favorite = new Favorite;

        $favorite->fill([
            'user_id' => 1,
            'book_id' => 1,
        ]);

        $this->assertEquals(1, $favorite->user_id);
        $this->assertEquals(1, $favorite->book_id);
    }

    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $favorite = new Favorite;

        $favorite->fill([
            'user_id' => 1,
            'admin_flag' => true,
        ]);

        $this->assertEquals(1, $favorite->user_id);
        $this->assertArrayNotHasKey('admin_flag', $favorite->getAttributes());
    }

    public function test_favorite_belongs_to_user(): void
    {
        $favorite = new Favorite;

        $relation = $favorite->user();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );
    }

    public function test_favorite_belongs_to_book(): void
    {
        $favorite = new Favorite;

        $relation = $favorite->book();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            Book::class,
            $relation->getRelated()
        );
    }
}
