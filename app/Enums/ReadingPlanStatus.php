<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Overdue = 'overdue';

    /**
     * 読書計画のステータスを日本語で取得する
     *
     * @return string ステータスの日本語表記
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => '進行中',
            self::Completed => '完了',
            self::Overdue => '期限切れ',
        };
    }

    /**
     * ステータスに応じたバッジのCSSクラスを取得する
     *
     * @return string CSSクラス
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Overdue => 'bg-red-100 text-red-800',
        };
    }
}
