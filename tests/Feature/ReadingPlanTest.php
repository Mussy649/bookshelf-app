<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Http\Controllers\ReadingPlanController;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reading-plans.store'),
            [
                'book_id' => $book->id,
                'target_date' => today()->addDay()->toDateString(),
            ]
        );

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を登録しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_past_target_date_cannot_be_stored(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reading-plans.store'),
            [
                'book_id' => $book->id,
                'target_date' => today()->subDay()->toDateString(),
            ]
        );

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_duplicate_uncompleted_plan_cannot_be_stored(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->createPlan($user, $book);

        $response = $this->actingAs($user)->post(
            route('reading-plans.store'),
            [
                'book_id' => $book->id,
                'target_date' => today()->addDays(2)->toDateString(),
            ]
        );

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_index_contains_only_authenticated_users_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $ownPlan = $this->createPlan($user, $book);
        $otherPlan = $this->createPlan($otherUser, $book);

        $readingPlans = $this->getReadingPlans($user);

        $this->assertTrue($readingPlans->contains('id', $ownPlan->id));
        $this->assertFalse($readingPlans->contains('id', $otherPlan->id));
    }

    public function test_index_can_filter_by_status_and_show_all(): void
    {
        $user = User::factory()->create();

        $inProgress = $this->createPlan(
            $user,
            Book::factory()->create(),
            ReadingPlanStatus::InProgress
        );

        $completed = $this->createPlan(
            $user,
            Book::factory()->create(),
            ReadingPlanStatus::Completed
        );

        $expired = $this->createPlan(
            $user,
            Book::factory()->create(),
            ReadingPlanStatus::Expired
        );

        $filteredPlans = $this->getReadingPlans(
            $user,
            ReadingPlanStatus::Completed->value
        );

        $this->assertSame(
            [$completed->id],
            $filteredPlans->pluck('id')->all()
        );

        $allPlans = $this->getReadingPlans($user);

        $this->assertEqualsCanonicalizing(
            [$inProgress->id, $completed->id, $expired->id],
            $allPlans->pluck('id')->all()
        );
    }

    private function getReadingPlans(
        User $user,
        ?string $status = null
    ): Collection {
        $query = $status === null ? [] : ['status' => $status];

        $request = Request::create('/reading-plans', 'GET', $query);
        $request->setUserResolver(fn (): User => $user);

        $view = app(ReadingPlanController::class)->index($request);

        return $view->getData()['readingPlans'];
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
