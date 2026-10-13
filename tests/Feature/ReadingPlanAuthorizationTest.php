<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = $this->createPlan($user, $book);

        $response = $this->actingAs($user)->post(
            route('reading-plans.complete', $plan)
        );

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読了しました。');

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );
        $this->assertNotNull($plan->completed_at);
    }

    public function test_other_user_cannot_operate_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $plan = $this->createPlan($owner, $book);

        $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()->addDays(3)->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $plan))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_completed_plan_cannot_be_edited_updated_or_completed_again(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $plan = $this->createPlan(
            $user,
            $book,
            ReadingPlanStatus::Completed
        );

        $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => today()->addDays(3)->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('reading-plans.complete', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_owner_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $plan = $this->createPlan($user, $book);

        $response = $this->actingAs($user)->delete(
            route('reading-plans.destroy', $plan)
        );

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を削除しました。');

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    private function createPlan(
        User $user,
        Book $book,
        ReadingPlanStatus $status = ReadingPlanStatus::InProgress
    ): ReadingPlan {
        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => $status,
            'completed_at' => $status === ReadingPlanStatus::Completed
                ? now()
                : null,
        ]);
    }
}
