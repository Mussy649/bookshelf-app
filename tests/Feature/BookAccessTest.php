<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * No.5
     * 未ログインでも書籍一覧を閲覧できる
     */
    public function test_guest_can_view_books_index(): void
    {
        $book = Book::factory()->create();

        $response = $this->get('/books');

        $response->assertStatus(200);
        $response->assertSee($book->title);
    }

    /**
     * No.6
     * 未ログインでも書籍詳細を閲覧できる
     */
    public function test_guest_can_view_book_show(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee($book->title);
        $response->assertSee($book->author);
        $response->assertSee($book->isbn);
    }

    /**
     * No.7
     * 未ログインで保護画面へアクセスすると
     * ログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_accessing_protected_pages(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect('/login');

        $this->get(route('favorites.index'))
            ->assertRedirect('/login');

        $this->get(route('genres.index'))
            ->assertRedirect('/login');
    }

    /**
     * No.9
     * 書籍一覧が10件単位でページネーションされ、
     * 最新の書籍から表示される
     */
    public function test_books_index_is_paginated_by_ten_and_ordered_latest_first(): void
    {
        $oldestBook = Book::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->count(9)->create([
            'created_at' => now()->subDay(),
        ]);

        $newestBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get('/books');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($newestBook) {
            return $books->count() === 10
                && $books->total() === 11
                && $books->first()->is($newestBook);
        });

        $secondPageResponse = $this->get('/books?page=2');

        $secondPageResponse->assertStatus(200);
        $secondPageResponse->assertViewHas('books', function ($books) use ($oldestBook) {
            return $books->count() === 1
                && $books->total() === 11
                && $books->first()->is($oldestBook);
        });
    }
}
