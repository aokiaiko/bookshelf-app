<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ReadingPlan $readingPlan,
        public string $timing
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {

        $bookTitle = $this->readingPlan->book->title;

        $body = match ($this->timing) {
            'three_days_before' => "「{$bookTitle}」の期限まで残り 3 日です。引き続き読書を進めましょう。",
            'on_due_date' => "「{$bookTitle}」は本日が期限です。読了済みなら完了登録を、もう少し必要なら期限を変更してください。",
            'three_days_after' => "「{$bookTitle}」の期限から 3 日が経過しました。読了済みなら完了登録、続けるなら期限を変更してください。",
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing,
            'title' => '読書計画のリマインダー',
            'body' => $body,
        ];
    }
}
