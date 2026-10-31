<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_book_show_displays_review_like_icon(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $response = $this->actingAs($user)->get(route('books.show', $review->book));

        $response->assertStatus(200);
        $response->assertSee('action="'.route('reviews.like', $review).'"', false);
        $response->assertSee('いいね');
    }

    public function test_user_can_like_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $review->book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $review->book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)->get(route('books.show', $review->book));

        $response->assertStatus(200);
        $response->assertSee('いいね済み (1)');
        $response->assertSee('text-blue-500', false);
    }

    public function test_user_can_unlike_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $user->likedReviews()->attach($review->id);

        $response = $this->actingAs($user)
            ->from(route('books.show', $review->book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $review->book));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response = $this->get(route('books.show', $review->book));

        $response->assertStatus(200);
        $response->assertSee('いいね (0)');
        $response->assertSee('text-gray-500 hover:text-blue-500', false);
    }

    public function test_review_liked_returns_404_when_review_does_not_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reviews.like', 99999));

        $response->assertNotFound();
    }
}
