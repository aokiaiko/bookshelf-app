<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_store_review(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);
    }

    public function test_book_show_displays_review_form(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('<select name="rating"', false);
        $response->assertSee('name="comment"', false);
        $response->assertSee('投稿する');
    }

    public function test_review_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => '',
            'comment' => 'テストレビュー',
        ]);

        $response->assertSessionHasErrors([
            'rating' => '評価は必須です。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_review_rating_must_be_integer(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 'abc',
            'comment' => 'テストレビュー',
        ]);

        $response->assertSessionHasErrors([
            'rating' => '評価は1〜5の整数で入力してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_review_rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        foreach ([0, 6] as $rating) {
            $response = $this->actingAs($user)->post(route('reviews.store', $book), [
                'rating' => $rating,
                'comment' => 'テストレビュー',
            ]);

            $response->assertSessionHasErrors([
                'rating' => '評価は1〜5の整数で入力してください。',
            ]);

            $this->assertDatabaseMissing('reviews', [
                'book_id' => $book->id,
                'user_id' => $user->id,
            ]);
        }
    }

    public function test_review_comment_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => 'コメントは必須です。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_review_comment_must_be_string(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => ['テストレビュー'],
        ]);

        $response->assertSessionHasErrors([
            'comment' => 'コメントは文字列で入力してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_review_store_saves_review_and_redirects_to_book_show(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_review_store_displays_review_and_success_message(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テストレビュー');
        $response->assertSee('レビューを投稿しました。');
    }

    public function test_guest_cannot_update_review(): void
    {
        $review = Review::factory()->create([
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->put(route('reviews.update', $review), [
            'rating' => 4,
            'comment' => '更新後のレビュー',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '更新後のレビュー',
        ]);
    }

    public function test_review_owner_can_access_edit_page_with_existing_data(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->get(route('reviews.edit', $review));

        $response->assertStatus(200);
        $response->assertSee('name="rating" value="4"', false);
        $response->assertSee('value="4" class="sr-only peer" checked', false);
        $response->assertSee('name="comment"', false);
        $response->assertSee('更新前のレビュー');
    }

    public function test_review_owner_can_see_edit_button(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('編集');
        $response->assertSee(route('reviews.edit', $review));
    }

    public function test_non_owner_cannot_see_review_edit_button(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($userB)->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertDontSee(route('reviews.edit', $review));
    }

    public function test_non_owner_cannot_update_review(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($userB)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);
    }

    public function test_review_update_rating_is_required(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => '',
            'comment' => '更新後のレビュー',
        ]);

        $response->assertSessionHasErrors([
            'rating' => '評価は必須です。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);
    }

    public function test_review_update_rating_must_be_integer(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 'abc',
            'comment' => '更新後のレビュー',
        ]);

        $response->assertSessionHasErrors([
            'rating' => '評価は1〜5の整数で入力してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);
    }

    public function test_review_update_rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        foreach ([0, 6] as $rating) {
            $response = $this->actingAs($user)->put(route('reviews.update', $review), [
                'rating' => $rating,
                'comment' => '更新後のレビュー',
            ]);

            $response->assertSessionHasErrors([
                'rating' => '評価は1〜5の整数で入力してください。',
            ]);

            $this->assertDatabaseHas('reviews', [
                'id' => $review->id,
                'rating' => 3,
                'comment' => '更新前のレビュー',
            ]);
        }
    }

    public function test_review_update_comment_is_required(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => 'コメントは必須です。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);
    }

    public function test_review_update_comment_must_be_string(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => ['更新後のレビュー'],
        ]);

        $response->assertSessionHasErrors([
            'comment' => 'コメントは文字列で入力してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);
    }

    public function test_review_update_saves_review_and_redirects_to_book_show(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $review->book_id,
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);

        $response->assertRedirect(route('books.show', $review->book_id));
    }

    public function test_review_update_displays_review_and_success_message(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前のレビュー',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '更新後のレビュー',
        ]);

        $response->assertRedirect(route('books.show', $review->book_id));
        $response->assertSessionHas('success', 'レビューを更新しました。');

        $response = $this->get(route('books.show', $review->book_id));

        $response->assertStatus(200);
        $response->assertSee('★★★★★');
        $response->assertSee('更新後のレビュー');
        $response->assertDontSee('更新前のレビュー');
        $response->assertSee('レビューを更新しました。');
    }

    public function test_review_update_returns_404_when_review_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->put(route('reviews.update', 99999));

        $response->assertNotFound();
    }

    public function test_guest_cannot_delete_review(): void
    {
        $review = Review::factory()->create([
            'rating' => 3,
            'comment' => '削除前のレビュー',
        ]);

        $response = $this->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '削除前のレビュー',
        ]);
    }

    public function test_review_owner_can_see_delete_button(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('削除');
        $response->assertSee(
            'action="'.route('reviews.destroy', $review).'"',
            false
        );
    }

    public function test_non_owner_cannot_see_review_delete_button(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($userB)->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertDontSee(
            'action="'.route('reviews.destroy', $review).'"',
            false
        );
    }

    public function test_owner_can_delete_review_and_redirects_to_book_show(): void
    {
        $user = User::factory()->create();
        $likedUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $review->likedByUsers()->attach($likedUser->id);

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $review->book_id));
    }

    public function test_non_owner_cannot_delete_review(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userB)->delete(route('reviews.destroy', $review));

        $response->assertStatus(403);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $userA->id,
        ]);
    }

    public function test_review_destroy_returns_404_when_review_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->delete(route('reviews.destroy', 99999));

        $response->assertNotFound();
    }

    public function test_review_delete_displays_success_message(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $review->book_id));
        $response->assertSessionHas('success', 'レビューを削除しました。');

        $response = $this->get(route('books.show', $review->book_id));

        $response->assertStatus(200);
        $response->assertSee('レビューを削除しました。');
    }
}
