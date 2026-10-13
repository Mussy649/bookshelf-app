<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_book_cascades_basic_related_data(): void
    {
        $owner = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $likerUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);

        $favoriteUser->favoriteBooks()->attach($book->id);

        $review = Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'cascadeテスト用レビューです。',
        ]);

        $likerUser->likedReviews()->attach($review->id);

        $response = $this->actingAs($owner)->delete(
            route('books.destroy', $book)
        );

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }
}
