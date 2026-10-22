<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

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
}
