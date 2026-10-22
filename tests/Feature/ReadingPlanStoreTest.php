<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanStoreTest extends TestCase
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

    public function test_nonexistent_book_cannot_be_used_for_reading_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reading-plans.store'),
            [
                'book_id' => 999999,
                'target_date' => today()->addDay()->toDateString(),
            ]
        );

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_authenticated_user_can_view_create_page_with_books_ordered_by_title(): void
    {
        $user = User::factory()->create();

        $bookB = Book::factory()->create([
            'title' => 'B Book',
        ]);

        $bookA = Book::factory()->create([
            'title' => 'A Book',
        ]);

        $response = $this->actingAs($user)->get(
            route('reading-plans.create')
        );

        $response->assertOk();
        $response->assertViewIs('reading-plans.create');

        $response->assertViewHas('books', function ($books) use ($bookA, $bookB) {
            return $books->pluck('id')->all() === [
                $bookA->id,
                $bookB->id,
            ];
        });
    }
}
