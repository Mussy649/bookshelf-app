<?php

namespace Tests\Feature;

use App\Http\Controllers\MyReportController;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_calculates_review_count_books_read_and_average_rating(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $bookA = $this->createBook($user, '書籍A', '9781234567801');
        $bookB = $this->createBook($user, '書籍B', '9781234567802');
        $otherBook = $this->createBook($otherUser, '他ユーザー書籍', '9781234567803');

        $this->createReview($user, $bookA, 5);
        $this->createReview($user, $bookB, 3);
        $this->createReview($otherUser, $otherBook, 1);

        $stats = $this->getStats($user);

        $this->assertSame(2, $stats['summary']['total_reviews']);
        $this->assertSame(2, $stats['summary']['books_read']);
        $this->assertSame(4.0, $stats['summary']['average_rating']);
    }

    public function test_report_calculates_rating_distribution(): void
    {
        $user = User::factory()->create();

        $ratings = [1, 2, 3, 4, 5, 5];

        foreach ($ratings as $index => $rating) {
            $book = $this->createBook(
                $user,
                '評価書籍'.($index + 1),
                '97812345678'.str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT)
            );

            $this->createReview($user, $book, $rating);
        }

        $stats = $this->getStats($user);

        $this->assertSame(
            [1, 1, 1, 1, 2],
            $stats['rating_distribution']->all()
        );
    }

    public function test_report_returns_top_five_books_rated_four_or_higher(): void
    {
        $user = User::factory()->create();

        $ratings = [4, 5, 3, 4, 5, 4, 4];

        foreach ($ratings as $index => $rating) {
            $book = $this->createBook(
                $user,
                '高評価書籍'.($index + 1),
                '97812345679'.str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT)
            );

            $this->createReview($user, $book, $rating);
        }

        $stats = $this->getStats($user);
        $topRatedBooks = $stats['top_rated_books'];

        $this->assertCount(5, $topRatedBooks);
        $this->assertTrue(
            $topRatedBooks->every(
                fn (array $book): bool => $book['rating'] >= 4
            )
        );
        $this->assertSame(
            [5, 5, 4, 4, 4],
            $topRatedBooks->pluck('rating')->all()
        );
    }

    public function test_report_returns_top_five_genres_by_average_rating(): void
    {
        $user = User::factory()->create();

        $genreRatings = [
            'ジャンルA' => 2,
            'ジャンルB' => 5,
            'ジャンルC' => 1,
            'ジャンルD' => 4,
            'ジャンルE' => 3,
            'ジャンルF' => 1,
        ];

        foreach ($genreRatings as $index => $rating) {
            $genre = Genre::create([
                'name' => $index,
            ]);

            $book = $this->createBook(
                $user,
                $index.'書籍',
                '97812345700'.str_pad(
                    (string) $genre->id,
                    2,
                    '0',
                    STR_PAD_LEFT
                )
            );

            $book->genres()->attach($genre->id);

            $this->createReview($user, $book, $rating);
        }

        $stats = $this->getStats($user);
        $genreRatings = $stats['genre_ratings'];

        $this->assertCount(5, $genreRatings);
        $this->assertSame('ジャンルB', $genreRatings->first()['name']);
        $this->assertSame(5.0, $genreRatings->first()['average_rating']);
        $this->assertFalse(
            $genreRatings->contains(
                fn (array $genre): bool => $genre['name'] === 'ジャンルF'
            )
        );
    }

    private function getStats(User $user): array
    {
        $request = Request::create('/reports', 'GET');
        $request->setUserResolver(fn (): User => $user);

        $view = app(MyReportController::class)->index($request);

        return $view->getData()['stats'];
    }

    private function createBook(
        User $user,
        string $title,
        string $isbn
    ): Book {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
        ]);
    }

    private function createReview(
        User $user,
        Book $book,
        int $rating
    ): Review {
        return Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'comment' => 'テストレビュー',
        ]);
    }
}
