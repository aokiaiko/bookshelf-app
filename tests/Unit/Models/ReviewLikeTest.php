<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $reviewLike = new ReviewLike;

        $reviewLike->fill([
            'user_id' => 1,
            'review_id' => 1,
        ]);

        $this->assertEquals(1, $reviewLike->user_id);
        $this->assertEquals(1, $reviewLike->review_id);
    }

    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $reviewLike = new ReviewLike;

        $reviewLike->fill([
            'user_id' => 1,
            'admin_flag' => true,
        ]);

        $this->assertEquals(1, $reviewLike->user_id);
        $this->assertArrayNotHasKey(
            'admin_flag',
            $reviewLike->getAttributes()
        );
    }

    public function test_review_like_belongs_to_user(): void
    {
        $reviewLike = new ReviewLike;

        $relation = $reviewLike->user();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            User::class,
            $relation->getRelated()
        );
    }

    public function test_review_like_belongs_to_review(): void
    {
        $reviewLike = new ReviewLike;

        $relation = $reviewLike->review();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );

        $this->assertInstanceOf(
            Review::class,
            $relation->getRelated()
        );
    }
}
