<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    public function test_owner_can_update_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '更新前のコメントです。';
        $review->save();

        $response = $this->actingAs($owner)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => '更新後のコメントです。',
            ]
        );

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを更新しました。');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のコメントです。',
        ]);
    }

    public function test_non_owner_cannot_update_review_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '元のコメントです。';
        $review->save();

        $response = $this->actingAs($otherUser)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => '他人による変更です。',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメントです。',
        ]);
    }

    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 4;
        $review->comment = '削除するレビューです。';
        $review->save();

        $response = $this->actingAs($owner)->delete(
            route('reviews.destroy', $review)
        );

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを削除しました。');

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_non_owner_cannot_delete_review_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 4;
        $review->comment = '削除されてはいけないレビューです。';
        $review->save();

        $response = $this->actingAs($otherUser)->delete(
            route('reviews.destroy', $review)
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除されてはいけないレビューです。',
        ]);
    }

    public function test_owner_can_view_review_edit_page(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '編集前のコメントです。';
        $review->save();

        $response = $this->actingAs($owner)->get(
            route('reviews.edit', $review)
        );

        $response->assertOk();
        $response->assertViewIs('reviews.edit');
        $response->assertViewHas('review', $review);
    }

    public function test_non_owner_cannot_view_review_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '編集できないレビューです。';
        $review->save();

        $response = $this->actingAs($otherUser)->get(
            route('reviews.edit', $review)
        );

        $response->assertForbidden();
    }

    public function test_review_cannot_be_updated_without_rating_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '更新前のコメントです。';
        $review->save();

        $response = $this->actingAs($owner)->put(
            route('reviews.update', $review),
            [
                'rating' => null,
                'comment' => '変更後のコメントです。',
            ]
        );

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);
    }

    public function test_review_cannot_be_updated_with_rating_outside_one_to_five_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '更新前のコメントです。';
        $review->save();

        $response = $this->actingAs($owner)->put(
            route('reviews.update', $review),
            [
                'rating' => 6,
                'comment' => '変更後のコメントです。',
            ]
        );

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);
    }

    public function test_review_cannot_be_updated_when_comment_exceeds_1000_characters_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 3;
        $review->comment = '更新前のコメントです。';
        $review->save();

        $response = $this->actingAs($owner)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ]
        );

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
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
