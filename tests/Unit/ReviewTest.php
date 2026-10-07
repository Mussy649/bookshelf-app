<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID4-1: Review → User
     * user() belongsTo リレーション経由で、
     * そのレビューを投稿したUserを取得できる。
     */
    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '投稿者リレーションテスト用レビューです。',
        ]);

        $this->assertTrue($review->user->is($user));
    }

    /**
     * ID4-2: Review → Book
     * book() belongsTo リレーション経由で、
     * そのレビューが紐づく書籍を取得できる。
     */
    public function test_review_belongs_to_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '書籍リレーションテスト用レビューです。',
        ]);

        $this->assertTrue($review->book->is($book));
    }

    /**
     * ID4-3: Review → Liked Users
     * likedByUsers() belongsToMany（中間テーブル: review_likes）経由で、
     * そのレビューにいいねしたUserを取得できる。
     */
    public function test_review_belongs_to_many_liked_by_users(): void
    {
        $reviewer = User::factory()->create();
        $liker = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいねリレーションテスト用レビューです。',
        ]);

        $review->likedByUsers()->attach($liker->id);

        $this->assertTrue($review->likedByUsers->contains($liker));
        $this->assertCount(1, $review->likedByUsers);
    }
}
