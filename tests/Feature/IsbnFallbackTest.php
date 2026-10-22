<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_library_is_used_when_google_books_returns_no_results(): void
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
                'publish_date' => 'January 15, 2026',
                'covers' => [12345],
            ], 200),
            'https://openlibrary.org/authors/*' => Http::response([
                'name' => 'テスト著者',
            ], 200),
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', ['isbn' => $isbn]));

        $response->assertOk()->assertJson([
            'title' => 'Open Libraryのテスト書籍',
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-15',
            'description' => null,
            'image_url' => 'https://covers.openlibrary.org/b/id/12345-L.jpg',
        ]);

        Http::assertSentCount(3);
    }

    public function test_open_bd_is_used_when_previous_apis_return_no_results(): void
    {
        $isbn = '9781234567897';

        Http::fake([
            config('services.google_books.url').'*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
            'https://openlibrary.org/isbn/*' => Http::response(
                [],
                404
            ),
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

        $response->assertOk()->assertJson([
            'title' => 'openBDのテスト書籍',
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);

        Http::assertSentCount(3);

        Http::assertSent(function ($request) use ($isbn) {
            return str_starts_with(
                $request->url(),
                'https://api.openbd.jp/v1/get'
            ) && $request['isbn'] === $isbn;
        });
    }

    public function test_not_found_when_all_three_apis_have_no_book(): void
    {
        $isbn = '9781234567897';

        Http::fake([
            config('services.google_books.url').'*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
            'https://openlibrary.org/isbn/*' => Http::response([], 404),
            'https://api.openbd.jp/v1/get*' => Http::response([
                null,
            ], 200),
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', ['isbn' => $isbn]));

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);

        Http::assertSentCount(3);
    }

    public function test_google_books_http_error_does_not_trigger_fallback(): void
    {
        Http::fake([
            config('services.google_books.url').'*' => Http::response([], 500),
            '*' => Http::response([], 200),
        ]);

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

        Http::assertSentCount(1);

        Http::assertSent(function ($request) {
            return str_starts_with(
                $request->url(),
                config('services.google_books.url')
            );
        });
    }
}
