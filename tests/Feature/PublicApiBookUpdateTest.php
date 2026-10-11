<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicApiBookUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_can_update_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genreA = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $genreB = Genre::create([
            'name' => 'ジャンルB',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => '更新前です。',
            'image_url' => null,
        ]);

        $book->genres()->attach($genreA->id);

        $response = $this->putJson(
            route('api.v1.books.update', $book),
            [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-09-30',
                'description' => '更新後です。',
                'image_url' => null,
                'genres' => [$genreB->id],
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJsonPath('title', '更新後タイトル')
            ->assertJsonPath('genres.0.id', $genreB->id);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'isbn' => '9781234567897',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genreA->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genreB->id,
        ]);
    }

    public function test_public_api_returns_422_when_updating_to_duplicate_isbn(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $bookA = Book::create([
            'user_id' => $user->id,
            'title' => '更新対象書籍',
            'author' => '著者A',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => '別の書籍',
            'author' => '著者B',
            'isbn' => '9781234567898',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->putJson(
            route('api.v1.books.update', $bookA),
            [
                'title' => '変更後タイトル',
                'author' => '著者A',
                'isbn' => '9781234567898',
                'published_date' => '2026-09-30',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseHas('books', [
            'id' => $bookA->id,
            'title' => '更新対象書籍',
            'isbn' => '9781234567897',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $bookA->id,
            'title' => '変更後タイトル',
        ]);
    }

    public function test_public_api_returns_404_when_updating_nonexistent_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->putJson(
            '/api/v1/books/99999',
            [
                'title' => '存在しない書籍の更新',
                'author' => 'テスト著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-09-30',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response->assertStatus(404);
    }
}
