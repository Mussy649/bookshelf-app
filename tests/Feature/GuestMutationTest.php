<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_update_or_delete_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '既存書籍',
            'author' => '既存著者',
            'isbn' => '9784000000147',
        ]);

        $book->genres()->attach($genre->id);

        $storeResponse = $this->post(
            route('books.store'),
            [
                'title' => 'Guest登録書籍',
                'author' => 'Guest著者',
                'isbn' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $storeResponse->assertRedirect('/login');

        $updateResponse = $this->put(
            route('books.update', $book),
            [
                'title' => 'Guest更新タイトル',
                'author' => 'Guest更新著者',
                'isbn' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $updateResponse->assertRedirect('/login');

        $deleteResponse = $this->delete(
            route('books.destroy', $book)
        );

        $deleteResponse->assertRedirect('/login');

        $this->assertDatabaseMissing('books', [
            'title' => 'Guest登録書籍',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '既存書籍',
            'author' => '既存著者',
            'isbn' => '9784000000147',
        ]);
    }

    public function test_guest_cannot_update_or_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = new Review;
        $review->user_id = $owner->id;
        $review->book_id = $book->id;
        $review->rating = 4;
        $review->comment = '更新前レビューです。';
        $review->save();

        $updateResponse = $this->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => 'Guestによる変更です。',
            ]
        );

        $updateResponse->assertRedirect('/login');

        $deleteResponse = $this->delete(
            route('reviews.destroy', $review)
        );

        $deleteResponse->assertRedirect('/login');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '更新前レビューです。',
        ]);
    }

    public function test_guest_cannot_create_update_or_delete_genre(): void
    {
        $genre = Genre::factory()->create([
            'name' => '既存ジャンル',
        ]);

        $storeResponse = $this->post(
            route('genres.store'),
            [
                'name' => 'Guest登録ジャンル',
            ]
        );

        $storeResponse->assertRedirect('/login');

        $updateResponse = $this->put(
            route('genres.update', $genre),
            [
                'name' => 'Guest更新ジャンル',
            ]
        );

        $updateResponse->assertRedirect('/login');

        $deleteResponse = $this->delete(
            route('genres.destroy', $genre)
        );

        $deleteResponse->assertRedirect('/login');

        $this->assertDatabaseMissing('genres', [
            'name' => 'Guest登録ジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '既存ジャンル',
        ]);
    }
}
