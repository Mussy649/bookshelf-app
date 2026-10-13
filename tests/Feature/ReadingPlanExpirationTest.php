<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_in_progress_plan_is_changed_to_expired(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->status
        );

        $this->assertNull($plan->completed_at);
    }

    public function test_future_and_completed_plans_are_not_expired(): void
    {
        $user = User::factory()->create();

        $futureBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $completedBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $futurePlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $futureBook->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $completedAt = now()->subDay();

        $completedPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'target_date' => today()->subDays(2),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => $completedAt,
        ]);

        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        $futurePlan->refresh();
        $completedPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $futurePlan->status
        );
        $this->assertNull($futurePlan->completed_at);

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $completedPlan->status
        );
        $this->assertNotNull($completedPlan->completed_at);
        $this->assertSame(
            $completedAt->format('Y-m-d H:i:s'),
            $completedPlan->completed_at->format('Y-m-d H:i:s')
        );
    }
}
