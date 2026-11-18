<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reading_plan_index(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_reading_plan_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertStatus(200);
    }

    public function test_reading_plan_index_displays_book_information(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertSee($book->title);
        $response->assertSee($readingPlan->target_date->format('Y-m-d'));
        $response->assertSee($readingPlan->completed_at?->format('Y-m-d') ?? '');
        $response->assertSee($readingPlan->status->label());
        $response->assertSee('読了する');
        $response->assertSee('編集');
        $response->assertSee('削除');
    }

    public function test_reading_plan_displays_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertStatus(200);
        $response->assertSee('該当する読書計画はありません。');
    }

    public function test_reading_plan_index_displays_all_status(): void
    {
        $user = User::factory()->create();

        $readingPlans = collect([
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'status' => ReadingPlanStatus::InProgress,
            ]),
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]),
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'status' => ReadingPlanStatus::Overdue,
                'target_date' => now()->subDays(3),
            ]),
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        foreach ($readingPlans as $readingPlan) {
            $response->assertSee($readingPlan->book->title);
            $response->assertSee($readingPlan->status->label());

        }
    }

    public function test_reading_plan_rejects_status(): void
    {
        $user = User::factory()->create();

        $readingPlans = collect([
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'status' => ReadingPlanStatus::InProgress,
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => 'invalid',
            ]));

        $response->assertStatus(200);
        $response->assertSee('該当する読書計画はありません。');
    }

    public function test_reading_plan_display_only_selected_status(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create([
            'title' => '進行中の書籍',
        ]);

        $book2 = Book::factory()->create([
            'title' => '完了した書籍',
        ]);

        $book3 = Book::factory()->create([
            'title' => '期限切れの書籍',
        ]);
        $readingPlan1 = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $readingPlan2 = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $readingPlan3 = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::InProgress->value,
            ]));

        $response->assertSee('進行中の書籍');
        $response->assertDontSee('完了した書籍');
        $response->assertDontSee('期限切れの書籍');
    }

    public function test_reading_plan_index_displays_only_logged_in_user_plans(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => 'ユーザー1の書籍',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'ユーザー2の書籍',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user1->id,
            'book_id' => $book1->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user2->id,
            'book_id' => $book2->id,
        ]);

        $response = $this->actingAs($user1)
            ->get(route('reading-plans.index'));

        $response->assertStatus(200);
        $response->assertSee('ユーザー1の書籍');
        $response->assertDontSee('ユーザー2の書籍');
    }

    public function test_guest_cannot_access_reading_plan_create(): void
    {
        $response = $this->get(route('reading-plans.create'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_store_reading_plan(): void
    {
        $response = $this->post(route('reading-plans.store'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_reading_plan_book_id_is_required(): void
    {
        $user = User::factory()->create();

        $data = [
            'book_id' => '',
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertSessionHasErrors([
            'book_id' => '書籍を選択してください。',
        ]);
    }

    public function test_reading_plan_book_id_must_exist(): void
    {
        $user = User::factory()->create();

        $data = [
            'book_id' => 99999,
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertSessionHasErrors([
            'book_id' => '選択された書籍が存在しません。',
        ]);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_reading_plan_cannot_create_duplicate_in_progress_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertSessionHasErrors([
            'book_id' => 'この書籍は既に進行中の読書計画が存在します。',
        ]);

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_reading_plan_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => '',
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertSessionHasErrors([
            'target_date' => '期日は必須です。',
        ]);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_reading_plan_target_date_must_be_valid_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => 'invalid-date',
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertSessionHasErrors([
            'target_date' => '期日は有効な日付形式で入力してください。',
        ]);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_reading_plan_target_date_cannot_be_past(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください。',
        ]);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_reading_plan_create_displays_target_date_input(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.create'));

        $response->assertStatus(200);
        $response->assertSee('name="target_date"', false);
        $response->assertSee('type="date"', false);
    }

    public function test_reading_plan_can_create_with_today_target_date(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_reading_plan_can_create_successfully(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を作成しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_different_users_can_create_reading_plans_for_same_book(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $userA->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $userA->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($userB)
            ->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $userB->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_can_create_reading_plan_for_book_with_completed_plan(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_can_create_reading_plan_for_book_with_overdue_plan(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $data = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_guest_cannot_access_reading_plan_edit(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_other_user_cannot_access_reading_plan_edit(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userB)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(403);
    }

    public function test_other_user_cannot_update_reading_plan(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
        ]);

        $data = [
            'target_date' => now()->addDays(10)->toDateString(),
        ];

        $response = $this->actingAs($userB)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $userA->id,
            'target_date' => $readingPlan->target_date->toDateString(),
        ]);

    }

    public function test_owner_can_access_reading_plan_edit(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(200);
    }

    public function test_user_cannot_store_non_exists_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = 9999;

        $data = [
            'target_date' => now()->addDays(10)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertStatus(404);
    }

    public function test_owner_cannot_access_completed_reading_plan_edit(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(403);
    }

    public function test_reading_plan_edit_displays_target_date_input(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(200);
        $response->assertSee('name="target_date"', false);
        $response->assertSee('type="date"', false);
        $response->assertSee(
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_user_cannot_access_nonexistent_reading_plan_edit(): void
    {
        $user = User::factory()->create();
        $nonexistentId = 99999;

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $nonexistentId));

        $response->assertStatus(404);
    }

    public function test_guest_cannot_delete_reading_plan(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->delete(
            route('reading-plans.destroy', $readingPlan)
        );

        $response->assertStatus(302);
        $response->assertRedirect('/login');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_other_user_cannot_delete_reading_plan(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($userB)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $userA->id,
        ]);
    }

    public function test_owner_can_delete_reading_plan_successfully(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を削除しました。');

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $readingPlan->book->id,
        ]);
    }

    public function test_user_cannot_delete_nonexistent_reading_plan(): void
    {
        $user = User::factory()->create();
        $nonexistentId = 99999;

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $nonexistentId));

        $response->assertStatus(404);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_owner_can_delete_completed_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_owner_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $readingPlan->refresh();

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_other_user_cannot_complete_reading_plan(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($userB)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_owner_can_complete_overdue_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $readingPlan->refresh();

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_owner_cannot_complete_already_completed_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }
}
