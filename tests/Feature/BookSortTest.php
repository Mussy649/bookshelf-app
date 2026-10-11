<?php

namespace Tests\Feature;

use App\Http\Controllers\BookController;
use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_sorted_latest_first(): void
    {
        $user = User::factory()->create();

        $oldBook = $this->createBook(
            $user,
            '古い本',
            '9780000000101',
            '2026-01-01 00:00:00'
        );

        $newBook = $this->createBook(
            $user,
            '新しい本',
            '9780000000102',
            '2026-02-01 00:00:00'
        );

        $request = Request::create('/books', 'GET', [
            'sort' => 'latest',
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertSame($newBook->id, $books->getCollection()[0]->id);
        $this->assertSame($oldBook->id, $books->getCollection()[1]->id);
    }

    public function test_books_can_be_sorted_oldest_first(): void
    {
        $user = User::factory()->create();

        $oldBook = $this->createBook(
            $user,
            '古い本',
            '9780000000103',
            '2026-01-01 00:00:00'
        );

        $newBook = $this->createBook(
            $user,
            '新しい本',
            '9780000000104',
            '2026-02-01 00:00:00'
        );

        $request = Request::create('/books', 'GET', [
            'sort' => 'oldest',
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertSame($oldBook->id, $books->getCollection()[0]->id);
        $this->assertSame($newBook->id, $books->getCollection()[1]->id);
    }

    public function test_books_can_be_sorted_by_title_ascending(): void
    {
        $user = User::factory()->create();

        $this->createBook(
            $user,
            'Charlie',
            '9780000000105',
            '2026-01-01 00:00:00'
        );

        $this->createBook(
            $user,
            'Alpha',
            '9780000000106',
            '2026-01-02 00:00:00'
        );

        $this->createBook(
            $user,
            'Bravo',
            '9780000000107',
            '2026-01-03 00:00:00'
        );

        $request = Request::create('/books', 'GET', [
            'sort' => 'title',
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertSame(
            ['Alpha', 'Bravo', 'Charlie'],
            $books->getCollection()->pluck('title')->all()
        );
    }

    public function test_books_can_be_sorted_by_average_rating_and_unreviewed_books_are_last(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $highRatedBook = $this->createBook(
            $owner,
            '高評価の本',
            '9780000000108',
            '2026-01-01 00:00:00'
        );

        $lowRatedBook = $this->createBook(
            $owner,
            '低評価の本',
            '9780000000109',
            '2026-01-02 00:00:00'
        );

        $unreviewedBook = $this->createBook(
            $owner,
            'レビューなしの本',
            '9780000000110',
            '2026-01-03 00:00:00'
        );

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'comment' => '高評価',
        ]);

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
            'comment' => '低評価',
        ]);

        $request = Request::create('/books', 'GET', [
            'sort' => 'rating',
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertSame(
            [
                $highRatedBook->id,
                $lowRatedBook->id,
                $unreviewedBook->id,
            ],
            $books->getCollection()->pluck('id')->all()
        );
    }

    private function createBook(
        User $user,
        string $title,
        string $isbn,
        string $createdAt
    ): Book {
        $book = Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
        ]);

        $book->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $book->refresh();
    }
}
