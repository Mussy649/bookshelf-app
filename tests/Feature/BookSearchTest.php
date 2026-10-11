<?php

namespace Tests\Feature;

use App\Http\Controllers\BookController;
use App\Models\Book;
use App\Models\User;
use App\Models\Genre;
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

    public function test_books_can_be_filtered_by_genre(): void
    {
        $user = User::factory()->create();

        $novel = Genre::create([
            'name' => '小説',
        ]);

        $technology = Genre::create([
            'name' => '技術書',
        ]);

        $novelBook = Book::create([
            'user_id' => $user->id,
            'title' => '小説の本',
            'author' => '著者A',
            'isbn' => '9780000000005',
            'published_date' => '2026-01-01',
        ]);

        $technologyBook = Book::create([
            'user_id' => $user->id,
            'title' => '技術の本',
            'author' => '著者B',
            'isbn' => '9780000000006',
            'published_date' => '2026-01-02',
        ]);

        $novelBook->genres()->attach($novel->id);
        $technologyBook->genres()->attach($technology->id);

        $request = Request::create('/books', 'GET', [
            'genre' => $technology->id,
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertCount(1, $books);
        $this->assertSame('技術の本', $books->first()->title);
    }

    public function test_keyword_and_genre_can_be_combined(): void
    {
        $user = User::factory()->create();

        $technology = Genre::create([
            'name' => '技術書',
        ]);

        $novel = Genre::create([
            'name' => '小説',
        ]);

        $matchedBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel開発',
            'author' => '著者A',
            'isbn' => '9780000000007',
            'published_date' => '2026-01-01',
        ]);

        $wrongGenreBook = Book::create([
            'user_id' => $user->id,
            'title' => 'Laravel物語',
            'author' => '著者B',
            'isbn' => '9780000000008',
            'published_date' => '2026-01-02',
        ]);

        $wrongKeywordBook = Book::create([
            'user_id' => $user->id,
            'title' => 'PHP開発',
            'author' => '著者C',
            'isbn' => '9780000000009',
            'published_date' => '2026-01-03',
        ]);

        $matchedBook->genres()->attach($technology->id);
        $wrongGenreBook->genres()->attach($novel->id);
        $wrongKeywordBook->genres()->attach($technology->id);

        $request = Request::create('/books', 'GET', [
            'keyword' => 'Laravel',
            'genre' => $technology->id,
        ]);

        $view = app(BookController::class)->index($request);
        $books = $view->getData()['books'];

        $this->assertCount(1, $books);
        $this->assertSame('Laravel開発', $books->first()->title);
    }
}
