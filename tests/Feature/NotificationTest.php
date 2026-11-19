<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notifications_index(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_notifications_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);
    }

    public function test_notification_display_each_notifications(): void
    {
        $user = User::factory()->create();

        $readingPlanA = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create([
                'title' => 'テスト書籍A',
            ])->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(3)->toDateString(),
        ]);

        $readingPlanB = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create([
                'title' => 'テスト書籍B',
            ])->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->toDateString(),
        ]);
        $readingPlanC = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create([
                'title' => 'テスト書籍C',
            ])->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $user->notify(new ReadingPlanReminder(
            $readingPlanA,
            'three_days_before'
        ));

        $user->notify(new ReadingPlanReminder(
            $readingPlanB,
            'on_due_date'
        ));

        $user->notify(new ReadingPlanReminder(
            $readingPlanC,
            'three_days_after'
        ));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200)
            ->assertSee('「テスト書籍A」の期限まで残り 3 日です。引き続き読書を進めましょう。')
            ->assertSee('「テスト書籍B」は本日が期限です。読了済みなら完了登録を、もう少し必要なら期限を変更してください。')
            ->assertSee('「テスト書籍C」の期限から 3 日が経過しました。読了済みなら完了登録、続けるなら期限を変更してください。');
    }

    public function test_notification_display_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200)
            ->assertSee('通知はありません。');

    }

    public function test_others_notification_non_display(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlanA = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
            'book_id' => Book::factory()->create([
                'title' => 'テスト書籍A',
            ])->id,
        ]);

        $readingPlanB = ReadingPlan::factory()->create([
            'user_id' => $userB->id,
            'book_id' => Book::factory()->create([
                'title' => 'テスト書籍B',
            ])->id,
        ]);

        $userA->notify(new ReadingPlanReminder(
            $readingPlanA,
            'three_days_before'
        ));

        $userB->notify(new ReadingPlanReminder(
            $readingPlanB,
            'on_due_date'
        ));
        $response = $this->actingAs($userA)->get(route('notifications.index'));

        $response->assertStatus(200)
            ->assertSee('テスト書籍A')
            ->assertDontSee('テスト書籍B');
    }

    public function test_user_update_notification(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->notify(new ReadingPlanReminder(
            $readingPlan,
            'three_days_before'
        ));

        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        $response->assertStatus(302)
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知を既読にしました。');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $userB->id,
        ]);

        $userB->notify(new ReadingPlanReminder(
            $readingPlan,
            'three_days_before'
        ));

        $notification = $userB->notifications()->first();

        $response = $this->actingAs($userA)
            ->post(route('notifications.read', $notification->id));

        $response->assertStatus(404);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_guest_cannot_update_notification(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->notify(new ReadingPlanReminder(
            $readingPlan,
            'three_days_before'
        ));

        $notification = $user->notifications()->first();

        $response = $this->post(route('notifications.read', $notification->id));

        $response->assertStatus(302);
        $response->assertRedirect('/login');

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }
}
