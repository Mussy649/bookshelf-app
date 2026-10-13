<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_days_before_reminder_is_created(): void
    {
        [$user, $plan] = $this->createPlan(
            today()->addDays(3)->toDateString()
        );

        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        $notifications = $user->notifications()->get();

        $this->assertCount(1, $notifications);

        $notification = $notifications->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame(
            ReadingPlanReminderNotification::TIMING_THREE_DAYS_BEFORE,
            $notification->data['timing']
        );
        $this->assertSame(
            $plan->id,
            $notification->data['reading_plan_id']
        );
    }

    public function test_due_date_reminder_is_created(): void
    {
        [$user, $plan] = $this->createPlan(
            today()->toDateString()
        );

        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        $notifications = $user->notifications()->get();

        $this->assertCount(1, $notifications);

        $notification = $notifications->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame(
            ReadingPlanReminderNotification::TIMING_ON_DUE_DATE,
            $notification->data['timing']
        );
        $this->assertSame(
            $plan->id,
            $notification->data['reading_plan_id']
        );
    }

    public function test_three_days_after_re_engagement_notification_is_created(): void
    {
        [$user, $plan] = $this->createPlan(
            today()->subDays(3)->toDateString()
        );

        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->status
        );

        $notifications = $user->notifications()->get();

        $this->assertCount(1, $notifications);

        $notification = $notifications->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame(
            ReadingPlanReminderNotification::TIMING_THREE_DAYS_AFTER,
            $notification->data['timing']
        );
        $this->assertSame(
            $plan->id,
            $notification->data['reading_plan_id']
        );
    }

    /**
     * @return array{0: User, 1: ReadingPlan}
     */
    private function createPlan(string $targetDate): array
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return [$user, $plan];
    }
}
