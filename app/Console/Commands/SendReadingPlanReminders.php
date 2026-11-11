<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendReadingPlanReminders extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '読書計画のリマインダー通知を送信する';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        $readingPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed)
            ->whereIn('target_date', [
                $today->copy()->addDays(3)->toDateString(),
                $today->toDateString(),
                $today->copy()->subDays(3)->toDateString(),
            ])
            ->get();

        foreach ($readingPlans as $readingPlan) {
            $targetDate = $readingPlan->target_date;

            $timing = match ($targetDate->toDateString()) {
                $today->copy()->addDays(3)->toDateString() => 'three_days_before',
                $today->toDateString() => 'on_due_date',
                $today->copy()->subDays(3)->toDateString() => 'three_days_after',
                default => null,
            };

            if ($timing === null) {
                continue;
            }

            Notification::send(
                $readingPlan->user,
                new ReadingPlanReminder($readingPlan, $timing)
            );
        }
    }
}
