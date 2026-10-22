<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeleteTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_deleting_review_cascades_review_likes(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 4;
        $review->comment = 'cascadeテスト用レビューです。';
        $review->save();

        $liker->likedReviews()->attach($review->id);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $liker->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($owner)->delete(
            route('reviews.destroy', $review)
        );

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }
}
