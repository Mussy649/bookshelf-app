<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

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
}
