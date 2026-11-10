<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class UpdateOverdueReadingPlans extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'app:update-overdue-reading-plans';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '期限切れの読書計画のステータスを更新する';

    /**
     * 期限切れの読書計画を更新する。
     */
    public function handle(): void
    {
        ReadingPlan::where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Overdue,
            ]);
    }
}
