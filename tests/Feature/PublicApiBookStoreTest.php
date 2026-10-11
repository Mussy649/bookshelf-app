<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicApiBookStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_can_create_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genreA = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $genreB = Genre::create([
            'name' => 'ジャンルB',
        ]);

        $response = $this->postJson(
            route('api.v1.books.store'),
            [
                'title' => 'API登録テスト書籍',
                'author' => '登録テスト著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-09-30',
                'description' => 'POST APIのテストです。',
                'image_url' => null,
                'genres' => [$genreA->id, $genreB->id],
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('title', 'API登録テスト書籍')
            ->assertJsonCount(2, 'genres');

        $bookId = $response->json('id');

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'user_id' => $user->id,
            'title' => 'API登録テスト書籍',
            'isbn' => '9781234567897',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $genreA->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $genreB->id,
        ]);
    }

    public function test_public_api_returns_422_when_isbn_is_duplicated(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'ジャンルA',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => '既存書籍',
            'author' => '既存著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->postJson(
            route('api.v1.books.store'),
            [
                'title' => '重複ISBN書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-09-30',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseMissing('books', [
            'title' => '重複ISBN書籍',
        ]);
    }

    public function test_public_api_returns_422_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.books.store'),
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_public_api_store_returns_japanese_validation_messages(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.books.store'),
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'タイトルは必須です。');
    }
}
