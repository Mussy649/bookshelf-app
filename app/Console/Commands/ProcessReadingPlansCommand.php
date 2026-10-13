<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ProcessReadingPlansCommand extends Command
{
    protected $signature = 'reading-plans:process-daily';

    protected $description = 'Process daily reading plan reminders and expiration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->sendReminderNotifications(
            today()->addDays(3),
            ReadingPlanReminderNotification::TIMING_THREE_DAYS_BEFORE
        );

        $this->sendReminderNotifications(
            today(),
            ReadingPlanReminderNotification::TIMING_ON_DUE_DATE
        );

        $this->expireOverduePlans();

        $this->sendReEngagementNotifications();

        $this->info('Reading plan daily processing completed.');

        return self::SUCCESS;
    }

    /**
     * Send reminder notifications for the specified target date.
     */
    private function sendReminderNotifications(
        CarbonInterface $targetDate,
        string $timing
    ): void {
        ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', $targetDate)
            ->get()
            ->each(function (ReadingPlan $plan) use ($timing): void {
                Notification::send(
                    $plan->user,
                    new ReadingPlanReminderNotification($plan, $timing)
                );
            });
    }

    /**
     * Mark overdue incomplete plans as expired.
     */
    private function expireOverduePlans(): void
    {
        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);
    }

    /**
     * Send re-engagement notifications three days after the target date.
     */
    private function sendReEngagementNotifications(): void
    {
        ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired->value)
            ->whereDate('target_date', today()->subDays(3))
            ->get()
            ->each(function (ReadingPlan $plan): void {
                Notification::send(
                    $plan->user,
                    new ReadingPlanReminderNotification(
                        $plan,
                        ReadingPlanReminderNotification::TIMING_THREE_DAYS_AFTER
                    )
                );
            });
    }
}
