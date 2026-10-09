<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumBookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_book_apis_are_public_without_bearer_token(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '公開APIテスト書籍',
            'author' => '公開API著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $this->getJson(route('api.v1.books.index'))
            ->assertOk();

        $this->getJson(route('api.v1.books.show', $book))
            ->assertOk();
    }

    public function test_post_book_api_returns_401_without_bearer_token(): void
    {
        $response = $this->postJson(
            route('api.v1.books.store'),
            []
        );

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_post_automatically_assigns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->postJson(
            route('api.v1.books.store'),
            [
                'title' => 'Sanctum登録テスト書籍',
                'author' => 'Sanctum登録著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-09-30',
                'description' => 'Bearerトークンからユーザーを取得します。',
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('title', 'Sanctum登録テスト書籍');

        $bookId = $response->json('id');

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'user_id' => $user->id,
            'title' => 'Sanctum登録テスト書籍',
        ]);
    }

    public function test_owner_can_update_and_delete_book_with_bearer_token(): void
    {
        $owner = User::factory()->create();

        $genreA = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $genreB = Genre::create([
            'name' => 'ジャンルB',
        ]);

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($genreA->id);

        $token = $owner->createToken('owner-token')->plainTextToken;

        $this->withToken($token)->putJson(
            route('api.v1.books.update', $book),
            [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-10-01',
                'description' => '所有者による更新',
                'image_url' => null,
                'genres' => [$genreB->id],
            ]
        )
            ->assertOk()
            ->assertJsonPath('title', '更新後タイトル');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新後タイトル',
        ]);

        $this->withToken($token)->deleteJson(
            route('api.v1.books.destroy', $book)
        )
            ->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_non_owner_cannot_update_or_delete_book_with_bearer_token(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $book = Book::create([
            'user_id' => $owner->id,
            'title' => '所有者の書籍',
            'author' => '所有者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($genre->id);

        $token = $otherUser->createToken('other-user-token')->plainTextToken;

        $this->withToken($token)->putJson(
            route('api.v1.books.update', $book),
            [
                'title' => '不正な更新タイトル',
                'author' => '別ユーザー',
                'isbn' => '9781234567897',
                'published_date' => '2026-10-01',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        )
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '所有者の書籍',
        ]);

        $this->withToken($token)->deleteJson(
            route('api.v1.books.destroy', $book)
        )
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }
}
