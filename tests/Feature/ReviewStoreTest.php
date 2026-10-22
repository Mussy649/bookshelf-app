<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_post_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => 'とても良い本でした。',
            ]
        );

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);
    }

    public function test_review_cannot_be_posted_without_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => null,
                'comment' => '評価を選択していません。',
            ]
        );

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_cannot_be_posted_with_rating_outside_one_to_five(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 6,
                'comment' => '評価範囲外のテストです。',
            ]
        );

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_cannot_be_posted_when_comment_exceeds_1000_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ]
        );

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_guest_cannot_post_review(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => '未ログイン投稿テストです。',
            ]
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);
    }

    public function test_reviews_are_displayed_latest_first(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $oldReview = new Review;
        $oldReview->user_id = $user->id;
        $oldReview->book_id = $book->id;
        $oldReview->rating = 3;
        $oldReview->comment = '古いレビューです。';
        $oldReview->created_at = now()->subDays(2);
        $oldReview->save();

        $middleReview = new Review;
        $middleReview->user_id = $user->id;
        $middleReview->book_id = $book->id;
        $middleReview->rating = 4;
        $middleReview->comment = '中間のレビューです。';
        $middleReview->created_at = now()->subDay();
        $middleReview->save();

        $latestReview = new Review;
        $latestReview->user_id = $user->id;
        $latestReview->book_id = $book->id;
        $latestReview->rating = 5;
        $latestReview->comment = '最新のレビューです。';
        $latestReview->created_at = now();
        $latestReview->save();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSeeInOrder([
            '最新のレビューです。',
            '中間のレビューです。',
            '古いレビューです。',
        ]);
    }
}
