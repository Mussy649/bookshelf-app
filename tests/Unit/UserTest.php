<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID1-1: User → Books
     * books() hasMany リレーション経由で、
     * そのUserが登録した書籍を取得できる。
     */
    public function test_user_has_many_books(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->books->contains($book));
        $this->assertCount(1, $user->books);
    }

    /**
     * ID1-2: User → Reviews
     * reviews() hasMany リレーション経由で、
     * そのUserが投稿したレビューを取得できる。
     */
    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => 'ユーザーリレーションテスト用レビューです。',
        ]);

        $this->assertTrue($user->reviews->contains($review));
        $this->assertCount(1, $user->reviews);
    }

    /**
     * ID1-3: User → Favorite Books
     * favoriteBooks() belongsToMany（中間テーブル: favorites）経由で、
     * そのUserがお気に入り登録した書籍を取得できる。
     */
    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $this->assertTrue($user->favoriteBooks->contains($book));
        $this->assertCount(1, $user->favoriteBooks);
    }

    /**
     * ID1-4: User → Liked Reviews
     * likedReviews() belongsToMany（中間テーブル: review_likes）経由で、
     * そのUserがいいねしたレビューを取得できる。
     */
    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいね対象レビューです。',
        ]);

        $user->likedReviews()->attach($review->id);

        $this->assertTrue($user->likedReviews->contains($review));
        $this->assertCount(1, $user->likedReviews);
    }
}
