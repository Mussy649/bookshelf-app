<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID2-1: Book → User
     * user() belongsTo リレーション経由で、
     * その書籍を登録したUserを取得できる。
     */
    public function test_book_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($book->user->is($user));
    }

    /**
     * ID2-2: Book → Genres
     * genres() belongsToMany（中間テーブル: book_genre）経由で、
     * その書籍に紐づいたジャンルを取得できる。
     */
    public function test_book_belongs_to_many_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $this->assertTrue($book->genres->contains($genre));
        $this->assertCount(1, $book->genres);
    }

    /**
     * ID2-3: Book → Reviews
     * reviews() hasMany リレーション経由で、
     * その書籍へ投稿されたレビューを取得できる。
     */
    public function test_book_has_many_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '書籍リレーションテスト用レビューです。',
        ]);

        $this->assertTrue($book->reviews->contains($review));
        $this->assertCount(1, $book->reviews);
    }
}
