<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $readingPlan = new ReadingPlan;

        $readingPlan->fill([
            'user_id' => 1,
            'book_id' => 1,
            'target_date' => '2026-11-20',
            'completed_at' => '2026-11-13 10:00:00',
            'status' => 'completed',
        ]);

        $this->assertEquals(1, $readingPlan->user_id);
        $this->assertEquals(1, $readingPlan->book_id);
        $this->assertEquals('2026-11-20', $readingPlan->target_date->format('Y-m-d'));
        $this->assertEquals('2026-11-13 10:00:00', $readingPlan->completed_at->format('Y-m-d H:i:s'));
        $this->assertEquals(ReadingPlanStatus::Completed, $readingPlan->status);

    }

    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $readingPlan = new ReadingPlan;

        $readingPlan->fill([
            'user_id' => 1,
            'admin_flag' => true,
        ]);

        $this->assertEquals(1, $readingPlan->user_id);
        $this->assertArrayNotHasKey('admin_flag', $readingPlan->getAttributes());
    }

    public function test_reading_plan_belongs_to_user(): void
    {
        $readingPlan = new ReadingPlan;

        $relation = $readingPlan->user();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );
    }

    public function test_reading_plan_belongs_to_book(): void
    {
        $readingPlan = new ReadingPlan;

        $relation = $readingPlan->book();

        $this->assertInstanceOf(
            BelongsTo::class,
            $relation
        );
    }
}
