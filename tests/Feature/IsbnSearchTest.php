<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_information_can_be_fetched_by_isbn(): void
    {
        Http::fake([
            '*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['テスト著者'],
                            'publishedDate' => '2026-01-15',
                            'description' => 'テスト説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', [
                'isbn' => '9781234567897',
            ]));

        $response
            ->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-01-15',
                'description' => 'テスト説明',
                'image_url' => 'https://example.com/book.jpg',
            ]);

        Http::assertSent(function ($request) {
            return $request['q'] === 'isbn:9781234567897';
        });
    }

    public function test_error_is_returned_when_book_is_not_found(): void
    {
        Http::fake([
            '*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn', [
                'isbn' => '9781234567897',
            ]));

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);
    }

    public function test_error_is_returned_when_google_books_api_fails(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
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
    }
}
