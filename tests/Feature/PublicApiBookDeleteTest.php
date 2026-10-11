<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicApiBookDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_can_delete_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '削除対象書籍',
            'author' => '削除対象著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($genre->id);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '削除確認用レビュー',
        ]);

        $response = $this->deleteJson(
            route('api.v1.books.destroy', $book)
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_public_api_returns_404_when_deleting_nonexistent_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/99999');

        $response->assertStatus(404);
    }
}
