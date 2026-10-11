<?php

namespace Tests\Feature;

use App\Http\Controllers\BookController;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_by_partial_title(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel実践入門',
            'author' => '山田太郎',
            'isbn' => '9780000000001',
            'published_date' => '2026-01-01',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => 'PHP基礎',
            'author' => '佐藤花子',
            'isbn' => '9780000000002',
            'published_date' => '2026-01-02',
        ]);

        $request = Request::create('/books', 'GET', [
            'keyword' => 'Laravel',
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertCount(1, $books);
        $this->assertSame('Laravel実践入門', $books->first()->title);
    }

    public function test_books_can_be_searched_by_partial_author_name(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => '技術書A',
            'author' => '山田太郎',
            'isbn' => '9780000000003',
            'published_date' => '2026-01-01',
        ]);

        Book::create([
            'user_id' => $user->id,
            'title' => '技術書B',
            'author' => '佐藤花子',
            'isbn' => '9780000000004',
            'published_date' => '2026-01-02',
        ]);

        $request = Request::create('/books', 'GET', [
            'keyword' => '山田',
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertCount(1, $books);
        $this->assertSame('技術書A', $books->first()->title);
    }
}
