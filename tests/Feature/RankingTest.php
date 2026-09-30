<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_ranking(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
    }

    public function test_books_are_ranked_by_average_review_rating_descending(): void
    {
        $user = User::factory()->create();

        $highBook = Book::factory()->create([
            'title' => '高評価の本',
        ]);

        $middleBook = Book::factory()->create([
            'title' => '中評価の本',
        ]);

        $lowBook = Book::factory()->create([
            'title' => '低評価の本',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $highBook->id,
            'rating' => 5,
            'comment' => null,
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $middleBook->id,
            'rating' => 4,
            'comment' => null,
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $lowBook->id,
            'rating' => 3,
            'comment' => null,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            '高評価の本',
            '中評価の本',
            '低評価の本',
        ]);
    }

    public function test_ranking_displays_only_top_ten_books_with_reviews(): void
    {
        $user = User::factory()->create();

        $lowestBook = Book::factory()->create([
            'title' => 'ランキング対象外の低評価本',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $lowestBook->id,
            'rating' => 1,
            'comment' => null,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $book = Book::factory()->create([
                'title' => "ランキング本{$i}",
            ]);

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => null,
            ]);
        }

        $noReviewBook = Book::factory()->create([
            'title' => 'レビューなしの本',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($books) {
            return $books->count() === 10;
        });

        $response->assertDontSee('ランキング対象外の低評価本');
        $response->assertDontSee('レビューなしの本');
    }
}
