<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanDisplayStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_plan_is_displayed_and_filtered_without_batch(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan(
            $user,
            today()->subDay()->toDateString()
        );

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk()
            ->assertSee('期限超過');

        $response->assertViewHas('readingPlans', function ($plans) use ($plan) {
            return $plans->contains(
                fn ($item) => $item->id === $plan->id
                    && $item->status === ReadingPlanStatus::Expired
            );
        });

        $filtered = $this->actingAs($user)->get(
            route('reading-plans.index', ['status' => 'expired'])
        );

        $filtered->assertOk()
            ->assertViewHas('readingPlans', function ($plans) use ($plan) {
                return $plans->pluck('id')->all() === [$plan->id];
            });

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $plan->fresh()->status
        );
    }

    public function test_today_and_completed_plans_keep_correct_status(): void
    {
        $user = User::factory()->create();

        $todayPlan = $this->createPlan(
            $user,
            today()->toDateString()
        );

        $completedPlan = $this->createPlan(
            $user,
            today()->subDays(2)->toDateString(),
            ReadingPlanStatus::Completed
        );

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk()
            ->assertViewHas('readingPlans', function ($plans) use (
                $todayPlan,
                $completedPlan
            ) {
                $today = $plans->firstWhere('id', $todayPlan->id);
                $completed = $plans->firstWhere('id', $completedPlan->id);

                return $today?->status === ReadingPlanStatus::InProgress
                    && $completed?->status === ReadingPlanStatus::Completed;
            });
    }

    public function test_plans_with_same_target_date_follow_registration_order(): void
    {
        $user = User::factory()->create();
        $date = today()->addDays(7)->toDateString();

        $first = $this->createPlan($user, $date);
        $second = $this->createPlan($user, $date);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk()
            ->assertViewHas('readingPlans', function ($plans) use (
                $first,
                $second
            ) {
                return $plans->pluck('id')->all() === [
                    $first->id,
                    $second->id,
                ];
            });
    }

    private function createPlan(
        User $user,
        string $targetDate,
        ReadingPlanStatus $status = ReadingPlanStatus::InProgress
    ): ReadingPlan {
        $book = Book::factory()->create();

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
            'completed_at' => $status === ReadingPlanStatus::Completed
                ? now()
                : null,
        ]);
    }
}
