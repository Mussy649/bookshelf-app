<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiBookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_can_get_books_index(): void
    {
        $response = $this->getJson(route('api.v1.books.index'));

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_public_api_books_include_genres_average_rating_and_review_count(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'APIテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => 'APIテスト用です。',
            'image_url' => null,
        ]);

        $genre = Genre::create([
            'name' => 'APIテストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'レビュー1',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'レビュー2',
        ]);

        $response = $this->getJson(route('api.v1.books.index'));

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.title', 'APIテスト書籍')
            ->assertJsonPath('data.0.genres.0.name', 'APIテストジャンル')
            ->assertJsonPath('data.0.reviews_count', 2);

        $this->assertEquals(
            4.0,
            (float) $response->json('data.0.reviews_avg_rating')
        );
    }

    public function test_public_api_can_search_books_by_keyword(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'PHP入門',
            'author' => '佐藤花子',
            'isbn' => '9781234567898',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->getJson(
            route('api.v1.books.index', ['keyword' => 'Laravel'])
        );

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel入門')
            ->assertJsonPath('total', 1);
    }

    public function test_public_api_can_filter_books_by_genre(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $genreB = Genre::create([
            'name' => 'ジャンルB',
        ]);

        $bookA = Book::create([
            'user_id' => $user->id,
            'title' => 'ジャンルAの書籍',
            'author' => '著者A',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $bookB = Book::create([
            'user_id' => $user->id,
            'title' => 'ジャンルBの書籍',
            'author' => '著者B',
            'isbn' => '9781234567898',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $bookA->genres()->attach($genreA->id);
        $bookB->genres()->attach($genreB->id);

        $response = $this->getJson(
            route('api.v1.books.index', ['genre' => $genreA->id])
        );

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'ジャンルAの書籍')
            ->assertJsonPath('total', 1);
    }

    public function test_public_api_can_search_and_filter_books_together(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::create([
            'name' => 'ジャンルA',
        ]);

        $genreB = Genre::create([
            'name' => 'ジャンルB',
        ]);

        $bookA = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '著者A',
            'isbn' => '9781234567897',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $bookB = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel実践',
            'author' => '著者B',
            'isbn' => '9781234567898',
            'published_date' => '2026-09-30',
            'description' => null,
            'image_url' => null,
        ]);

        $bookA->genres()->attach($genreA->id);
        $bookB->genres()->attach($genreB->id);

        $response = $this->getJson(
            route('api.v1.books.index', [
                'keyword' => 'Laravel',
                'genre' => $genreA->id,
            ])
        );

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel入門')
            ->assertJsonPath('total', 1);
    }

    public function test_public_api_paginates_books_by_ten(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            Book::create([
                'user_id' => $user->id,
                'title' => "APIテスト書籍{$i}",
                'author' => "著者{$i}",
                'isbn' => sprintf('9781234567%03d', $i),
                'published_date' => '2026-09-30',
                'description' => null,
                'image_url' => null,
            ]);
        }

        $response = $this->getJson(
            route('api.v1.books.index', ['page' => 2])
        );

        $response
            ->assertStatus(200)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 11)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_api_validates_per_page_boundaries(): void
    {
        // per_page=1 → 200 OK, per_page が 1
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 1])
        );
        $response
            ->assertStatus(200)
            ->assertJsonPath('per_page', 1);

        // per_page=10 → 200 OK, per_page が 10
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 10])
        );
        $response
            ->assertStatus(200)
            ->assertJsonPath('per_page', 10);

        // per_page=0 → 422, Validation error
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 0])
        );
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page'])
            ->assertJsonPath(
                'errors.per_page.0',
                'ページあたり件数は1以上10以下の整数で指定してください。'
            );

        // per_page=11 → 422, Validation error
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 11])
        );
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page'])
            ->assertJsonPath(
                'errors.per_page.0',
                'ページあたり件数は1以上10以下の整数で指定してください。'
            );
    }

    public function test_public_api_returns_422_for_invalid_genre(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', ['genre' => 99999])
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genre'])
            ->assertJsonPath(
                'errors.genre.0',
                '指定されたジャンルは存在しません。'
            );
    }

    public function test_public_api_returns_422_for_invalid_page(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', ['page' => 0])
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page'])
            ->assertJsonPath(
                'errors.page.0',
                'ページ番号は1以上の整数で指定してください。'
            );
    }

    public function test_public_api_returns_422_when_keyword_exceeds_255_characters(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', ['keyword' => str_repeat('a', 256)])
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['keyword'])
            ->assertJsonPath(
                'errors.keyword.0',
                'キーワードは255文字以内で指定してください。'
            );
    }

    // Index系10テスト
}
