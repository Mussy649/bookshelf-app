<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    public const TIMING_THREE_DAYS_BEFORE = 'three_days_before';

    public const TIMING_ON_DUE_DATE = 'on_due_date';

    public const TIMING_THREE_DAYS_AFTER = 'three_days_after';

    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly string $timing
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
     * Get the database representation of the notification.
     *
     * @return array<string, int|string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'timing' => $this->timing,
            'reading_plan_id' => $this->readingPlan->id,
        ];
    }

    private function title(): string
    {
        return match ($this->timing) {
            self::TIMING_THREE_DAYS_BEFORE => '読了予定日の3日前です',
            self::TIMING_ON_DUE_DATE => '今日は読了予定日です',
            self::TIMING_THREE_DAYS_AFTER => '読書計画を見直してみませんか',
            default => '読書計画のお知らせ',
        };
    }

    private function body(): string
    {
        $bookTitle = $this->readingPlan->book->title;

        return match ($this->timing) {
            self::TIMING_THREE_DAYS_BEFORE => "「{$bookTitle}」の読了予定日まであと3日です。",
            self::TIMING_ON_DUE_DATE => "「{$bookTitle}」は今日が読了予定日です。",
            self::TIMING_THREE_DAYS_AFTER => "「{$bookTitle}」の読了予定日から3日経過しました。",
            default => "「{$bookTitle}」の読書計画を確認してください。",
        };
    }
}
