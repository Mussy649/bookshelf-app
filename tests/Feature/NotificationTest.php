<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $notification = $this->createNotification($user);
        $otherNotification = $this->createNotification($otherUser);

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertViewIs('notifications.index')
            ->assertSee('読了予定日の3日前です')
            ->assertSee('未読')
            ->assertSee('既読にする')
            ->assertSee('1');

        $this->assertFalse(
            $response->getContent() === ''
        );

        $this->assertSame(
            $user->id,
            (int) $notification->notifiable_id
        );

        $this->assertNotSame(
            $user->id,
            (int) $otherNotification->notifiable_id
        );

        $readResponse = $this->actingAs($user)->post(
            route('notifications.read', $notification)
        );

        $readResponse
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas(
                'success',
                '通知を既読にしました。'
            );

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_other_user_cannot_mark_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $notification = $this->createNotification($owner);

        $response = $this->actingAs($otherUser)->post(
            route('notifications.read', $notification)
        );

        $response->assertForbidden();

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    private function createNotification(
        User $user
    ): DatabaseNotification {
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        Notification::send(
            $user,
            new ReadingPlanReminderNotification(
                $plan,
                ReadingPlanReminderNotification::TIMING_THREE_DAYS_BEFORE
            )
        );

        return $user->notifications()
            ->latest()
            ->firstOrFail();
    }
}
