<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTargetDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_in_progress_reading_plan_target_date(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(3)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_can_update_overdue_reading_plan_target_date(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_cannot_update_overdue_reading_plan_when_in_progress_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $readingPlanB = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlanB), $data);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'target_date' => 'この書籍は既に進行中の読書計画が存在します。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlanB->id,
            'target_date' => $readingPlanB->target_date->toDateString(),
            'status' => ReadingPlanStatus::Overdue->value,
        ]);
    }

    public function test_owner_cannot_access_completed_reading_plan_edit(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(403);
    }

    public function test_owner_cannot_update_completed_reading_plan_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $data = [
            'target_date' => now()->addDays(10)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'target_date' => $readingPlan->target_date->toDateString(),
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_owner_can_update_reading_plan_target_date_to_today(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(7)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_cannot_update_reading_plan_without_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $data = [
            'target_date' => '',
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertSessionHasErrors([
            'target_date' => '期日は必須です。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $readingPlan->target_date->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_cannot_update_reading_plan_with_invalid_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $data = [
            'target_date' => 'invalid-date',
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertSessionHasErrors([
            'target_date' => '期日は有効な日付形式で入力してください。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $readingPlan->target_date->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_cannot_update_reading_plan_with_past_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $data = [
            'target_date' => now()->subDay()->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $readingPlan->target_date->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_can_update_reading_plan_and_display_success_message(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(7)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDays(10)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $response->assertSessionHas(
            'success',
            '読書計画を更新しました。'
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_guest_cannot_update_reading_plan_target_date(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $data = [
            'target_date' => now()->addDays(10)->toDateString(),
        ];

        $response = $this->put(
            route('reading-plans.update', $readingPlan), $data
        );

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $readingPlan->target_date->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_other_user_cannot_update_reading_plan_target_date(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
            'status' => ReadingPlanStatus::InProgress,
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
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_user_cannot_update_nonexistent_reading_plan_target_date(): void
    {
        $user = User::factory()->create();

        $data = [
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', 9999), $data);

        $response->assertStatus(404);
    }

    public function test_owner_can_update_overdue_reading_plan_when_other_user_has_in_progress_plan(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $userA->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $readingPlanB = ReadingPlan::factory()->create([
            'user_id' => $userB->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($userB)
            ->put(route('reading-plans.update', $readingPlanB), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlanB->id,
            'user_id' => $userB->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_owner_can_update_overdue_reading_plan_when_completed_plan_exists(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $data = [
            'target_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), $data);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }
}
