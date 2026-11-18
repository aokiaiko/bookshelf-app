<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReadingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_my_reading_report_index(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_reading_plan_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200);
    }

    public function test_display_total_reviews(): void
    {
        $user = User::factory()->create();

        $reviews = Review::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['total_reviews'] === 3;
            });
    }

    public function test_display_completed_books(): void
    {
        $user = User::factory()->create();

        $readingPlans = ReadingPlan::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['books_read'] === 3;
            });
    }

    public function test_display_completed_books_unique(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlans = ReadingPlan::factory()->count(3)->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['books_read'] === 1;
            });
    }

    public function test_display_average_rating(): void
    {
        $user = User::factory()->create();

        foreach ([5, 4, 3] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['average_rating'] === 4;
            });
    }

    public function test_display_summary_when_no_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['total_reviews'] === 0
                    && $stats['summary']['books_read'] === 0
                    && $stats['summary']['average_rating'] == 0;
            });
    }

    public function test_display_rating_distribution(): void
    {
        $user = User::factory()->create();

        foreach ([5, 5, 4, 2] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['rating_distribution']->all() === [0, 1, 0, 1, 2];
            });
    }

    public function test_display_top_rated_books_in_descending_order(): void
    {
        $user = User::factory()->create();

        foreach ([4, 5, 4, 5, 5] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['top_rated_books']
                    ->pluck('rating')
                    ->values()
                    ->all() === [5, 5, 5, 4, 4];
            });
    }

    public function test_exclude_reviews_with_rating_3_or_less_from_top_rated_books(): void
    {
        $user = User::factory()->create();

        foreach ([3, 3, 2, 2, 1] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['top_rated_books']->isEmpty();
            });
    }

    public function test_access_book_show_from_top_rated_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertStatus(200);
    }

    public function test_display_genre_ratings(): void
    {
        $user = User::factory()->create();

        foreach ([2, 5, 3, 4, 1, 5] as $rating) {
            $genre = Genre::factory()->create();
            $book = Book::factory()->create();

            $book->genres()->attach($genre->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['genre_ratings']
                    ->pluck('average_rating')
                    ->values()
                    ->all() === [5, 5, 4, 3, 2];
            });
    }

    public function test_display_genre_average_rating_and_count(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        foreach ([5, 3] as $rating) {
            $book = Book::factory()->create();
            $book->genres()->attach($genre->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) use ($genre) {
                $genreRating = $stats['genre_ratings']->first();

                return $genreRating['id'] === $genre->id
                    && $genreRating['name'] === $genre->name
                    && $genreRating['count'] === 2
                    && $genreRating['average_rating'] == 4;
            });
    }

    public function test_display_review_in_multiple_genres(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::factory()->create(['name' => '小説']);
        $genreB = Genre::factory()->create(['name' => '文学']);

        $book = Book::factory()->create();
        $book->genres()->attach([$genreA->id, $genreB->id]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['genre_ratings']->count() === 2
                    && $stats['genre_ratings'][0]['count'] === 1
                    && $stats['genre_ratings'][0]['average_rating'] == 5
                    && $stats['genre_ratings'][1]['count'] === 1
                    && $stats['genre_ratings'][1]['average_rating'] == 5;
            });
    }

    public function test_genre_without_reviews_is_not_displayed(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::factory()->create(['name' => '小説']);
        $genreB = Genre::factory()->create(['name' => '歴史']);

        $bookA = Book::factory()->create();
        $bookA->genres()->attach($genreA->id);

        $bookB = Book::factory()->create();
        $bookB->genres()->attach($genreB->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['genre_ratings']->pluck('name')->all() === ['小説'];
            });
    }

    public function test_access_genre_show_from_high_rated_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertStatus(200);
    }

    public function test_other_users_reviews_are_not_counted(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Review::factory()->count(3)->create([
            'user_id' => $userA->id,
        ]);

        Review::factory()->count(2)->create([
            'user_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['total_reviews'] === 3;
            });
    }

    public function test_only_completed_reading_plans_are_counted(): void
    {
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Overdue,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['books_read'] === 1;
            });
    }
}
