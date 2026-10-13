<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_only_target_date_of_uncompleted_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $plan = $this->createPlan($user, $book);
        $newTargetDate = today()->addDays(14)->toDateString();

        $response = $this->actingAs($user)->put(
            route('reading-plans.update', $plan),
            [
                'book_id' => $otherBook->id,
                'target_date' => $newTargetDate,
            ]
        );

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas(
                'success',
                '読書計画を更新しました。'
            );

        $plan->refresh();

        $this->assertSame($book->id, $plan->book_id);
        $this->assertSame(
            $newTargetDate,
            $plan->target_date->toDateString()
        );
        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $plan->status
        );
    }

    public function test_past_target_date_cannot_be_used_when_updating_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $originalTargetDate = today()->addDays(7)->toDateString();

        $plan = $this->createPlan(
            $user,
            $book,
            $originalTargetDate
        );

        $response = $this->actingAs($user)->put(
            route('reading-plans.update', $plan),
            [
                'target_date' => today()->subDay()->toDateString(),
            ]
        );

        $response->assertSessionHasErrors('target_date');

        $plan->refresh();

        $this->assertSame(
            $originalTargetDate,
            $plan->target_date->toDateString()
        );
    }

    private function createPlan(
        User $user,
        Book $book,
        ?string $targetDate = null
    ): ReadingPlan {
        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate ?? today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
