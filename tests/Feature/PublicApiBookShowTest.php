<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiBookShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_can_get_book_detail(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'API詳細テスト書籍',
            'author' => '詳細テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => '詳細APIのテストです。',
            'image_url' => null,
        ]);

        $genre = Genre::create([
            'name' => '詳細テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '詳細APIのレビューです。',
        ]);

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertStatus(200)
            ->assertJsonPath('id', $book->id)
            ->assertJsonPath('title', 'API詳細テスト書籍')
            ->assertJsonPath('genres.0.name', '詳細テストジャンル')
            ->assertJsonPath('reviews.0.rating', 5)
            ->assertJsonPath('reviews.0.comment', '詳細APIのレビューです。')
            ->assertJsonPath('reviews_count', 1);

        $this->assertEquals(
            5.0,
            (float) $response->json('reviews_avg_rating')
        );
    }

    public function test_public_api_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertStatus(404);
    }

    // Show系2テスト
}
