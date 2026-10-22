<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnConnectionFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_bd_connection_failure_returns_502(): void
    {
        $isbn = '9781234567897';

        Http::fake([
            config('services.google_books.url').'*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
            'https://openlibrary.org/isbn/*' => Http::response([], 404),
            'https://api.openbd.jp/v1/get*' => function () {
                throw new ConnectionException('Connection timed out.');
            },
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', ['isbn' => $isbn]));

        $response
            ->assertStatus(502)
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。',
            ]);
    }

    public function test_open_library_author_connection_failure_falls_back_to_open_bd(): void
    {
        $isbn = '9781234567897';

        Http::fake([
            config('services.google_books.url').'*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
            'https://openlibrary.org/isbn/*' => Http::response([
                'isbn_13' => [$isbn],
                'title' => 'Open Libraryのテスト書籍',
                'authors' => [
                    ['key' => '/authors/OL123A'],
                ],
            ], 200),
            'https://openlibrary.org/authors/*' => function () {
                throw new ConnectionException('Connection timed out.');
            },
            'https://api.openbd.jp/v1/get*' => Http::response([
                [
                    'summary' => [
                        'isbn' => $isbn,
                        'title' => 'openBDのテスト書籍',
                        'author' => 'テスト著者',
                    ],
                ],
            ], 200),
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', ['isbn' => $isbn]));

        $response
            ->assertOk()
            ->assertJson([
                'title' => 'openBDのテスト書籍',
                'author' => 'テスト著者',
                'isbn' => $isbn,
                'published_date' => null,
            ]);

        Http::assertSent(function ($request) use ($isbn) {
            return str_starts_with(
                $request->url(),
                'https://api.openbd.jp/v1/get'
            ) && $request['isbn'] === $isbn;
        });
    }

    public function test_google_books_connection_failure_returns_502(): void
    {
        Http::fake(function () {
            throw new ConnectionException(
                'Connection timed out.'
            );
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', [
                'isbn' => '9781234567897',
            ]));

        $response
            ->assertStatus(502)
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。',
            ]);
    }

    public function test_open_library_connection_failure_falls_back_to_open_bd(): void
    {
        $isbn = '9781234567897';

        Http::fake([
            config('services.google_books.url').'*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
            'https://openlibrary.org/isbn/*' => function () {
                throw new ConnectionException(
                    'Connection timed out.'
                );
            },
            'https://api.openbd.jp/v1/get*' => Http::response([
                [
                    'summary' => [
                        'isbn' => $isbn,
                        'title' => 'openBDのテスト書籍',
                        'author' => 'テスト著者',
                    ],
                ],
            ], 200),
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', ['isbn' => $isbn]));

        $response
            ->assertOk()
            ->assertJson([
                'title' => 'openBDのテスト書籍',
                'author' => 'テスト著者',
                'isbn' => $isbn,
                'published_date' => null,
            ]);

        Http::assertSent(function ($request) use ($isbn) {
            return str_starts_with(
                $request->url(),
                'https://api.openbd.jp/v1/get'
            ) && $request['isbn'] === $isbn;
        });
    }
}
