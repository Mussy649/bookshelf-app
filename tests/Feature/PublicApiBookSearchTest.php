<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiBookSearchTest extends TestCase
{
    use RefreshDatabase;

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
}
