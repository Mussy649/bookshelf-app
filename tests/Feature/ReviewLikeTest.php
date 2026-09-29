<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_another_users_review(): void
    {
        $reviewOwner = User::factory()->create();
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいね対象のレビューです。',
        ]);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_can_remove_like_from_review(): void
    {
        $reviewOwner = User::factory()->create();
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => 'いいね解除対象です。',
        ]);

        $user->likedReviews()->attach($review->id);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_cannot_like_own_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '自分のレビューです。',
        ]);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }
}
