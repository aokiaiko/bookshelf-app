<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_sent_three_days_before(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 1);

        $notification = $user->notifications()->first();

        $this->assertEquals(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertEquals(
            'three_days_before',
            $notification->data['timing']
        );
    }

    public function test_reminder_sent_on_due_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->toDateString(),
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 1);

        $notification = $user->notifications()->first();

        $this->assertEquals(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertEquals(
            'on_due_date',
            $notification->data['timing']
        );
    }

    public function test_reminder_sent_three_days_after(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Expired,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 1);

        $notification = $user->notifications()->first();

        $this->assertEquals(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertEquals(
            'three_days_after',
            $notification->data['timing']
        );
    }

    public function test_reminder_not_sent_on_non_target_dates(): void
    {
        $user = User::factory()->create();

        foreach ([4, 1, -4] as $days) {
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => now()->addDays($days)->toDateString(),
            ]);
        }

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_reminder_not_sent_for_completed_plans(): void
    {
        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now()->toDateString(),
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_reminder_sent_to_each_plan_owner(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $readingPlanA = ReadingPlan::factory()->create([
            'user_id' => $userA->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->toDateString(),
        ]);

        $readingPlanB = ReadingPlan::factory()->create([
            'user_id' => $userB->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->toDateString(),
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 2);

        $this->assertEquals(
            $readingPlanA->id,
            $userA->notifications()->first()->data['reading_plan_id']
        );

        $this->assertEquals(
            $readingPlanB->id,
            $userB->notifications()->first()->data['reading_plan_id']
        );
    }

    public function test_reminder_command_is_scheduled_daily_at_20(): void
    {
        $schedule = app(Schedule::class);

        $event = collect($schedule->events())
            ->first(fn ($event) => str_contains(
                $event->command ?? '',
                'reading-plans:send-reminders'
            ));

        $this->assertNotNull($event);

        $this->assertEquals('0 20 * * *', $event->expression);

        $this->assertEquals('Asia/Tokyo', $event->timezone);
    }
}
