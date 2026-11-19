<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOverdueReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_reading_plan_is_updated(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Overdue->value,
        ]);
    }

    public function test_reading_plan_due_today_remains_in_progress(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->toDateString(),
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_reading_plan_due_tomorrow_remains_in_progress(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDay()->toDateString(),
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_completed_reading_plan_remains_completed(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'target_date' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('app:update-overdue-reading-plans')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_overdue_command_is_scheduled_daily_at_20(): void
    {
        $schedule = app(Schedule::class);

        $event = collect($schedule->events())
            ->first(fn ($event) => str_contains(
                $event->command ?? '',
                'app:update-overdue-reading-plans'
            ));

        $this->assertNotNull($event);

        $this->assertEquals('0 20 * * *', $event->expression);

        $this->assertEquals('Asia/Tokyo', $event->timezone);
    }
}
