<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $review = new Review;

        $review->fill([
            'user_id' => 1,
            'book_id' => 1,
            'rating' => 5,
            'comment' => 'とても良いです。',
        ]);

        $this->assertEquals(1, $review->user_id);
        $this->assertEquals(1, $review->book_id);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('とても良いです。', $review->comment);
    }

    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $review = new Review;

        $review->fill([
            'rating' => 5,
            'admin_flag' => true,
        ]);

        $this->assertEquals(5, $review->rating);
        $this->assertArrayNotHasKey('admin_flag', $review->getAttributes());
    }

    public function test_review_belongs_to_user(): void
    {
        $review = new Review;

        $relation = $review->user();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );
    }

    public function test_review_belongs_to_book(): void
    {
        $review = new Review;

        $relation = $review->book();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            Book::class,
            $relation->getRelated()
        );
    }

    public function test_review_belongs_to_many_liked_users(): void
    {
        $review = new Review;

        $relation = $review->likedByUsers();

        $this->assertInstanceOf(
            BelongsToMany::class,
            $relation
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );

        $this->assertEquals(
            'review_likes',
            $relation->getTable()
        );
    }
}
