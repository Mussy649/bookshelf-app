<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'Bookshelfテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000017',
            'published_date' => '2026-09-23',
            'description' => '書籍登録の正常系テストです。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasNoErrors();
        $book = Book::where('isbn', '9784000000017')->firstOrFail();
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍を登録しました。');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Bookshelfテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000017',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_book_cannot_be_created_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), []);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_cannot_be_created_with_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '9784000000017',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '重複ISBNテスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000017',
            'published_date' => '2026-09-25',
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['isbn']);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_book_cannot_be_created_without_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'ジャンル未選択テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000024',
            'published_date' => '2026-09-25',
            'description' => null,
            'image_url' => null,
            'genres' => [],
        ]);

        $response->assertSessionHasErrors(['genres']);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_owner_can_update_book_and_sync_genres(): void
    {
        $owner = User::factory()->create();

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'isbn' => '9784000000031',
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9784000000031',
                'published_date' => '2026-09-25',
                'description' => '更新後の説明です。',
                'image_url' => null,
                'genres' => [$newGenre->id],
            ]
        );

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍情報を更新しました。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000031',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    public function test_non_owner_cannot_update_book_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000048',
            'published_date' => '2026-09-01',
            'description' => '更新前の説明です。',
            'image_url' => null,
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this->actingAs($otherUser)->put(
            route('books.update', $book),
            [
                'title' => '不正な更新タイトル',
                'author' => '不正な更新著者',
                'isbn' => '9784000000048',
                'published_date' => '2026-09-25',
                'description' => '他ユーザーによる更新です。',
                'image_url' => null,
                'genres' => [$newGenre->id],
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正な更新タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->delete(
            route('books.destroy', $book)
        );

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を削除しました。');

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_non_owner_cannot_delete_book_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->delete(
            route('books.destroy', $book)
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_deleting_book_cascades_basic_related_data(): void
    {
        $owner = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $likerUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);

        $favoriteUser->favoriteBooks()->attach($book->id);

        $review = Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'cascadeテスト用レビューです。',
        ]);

        $likerUser->likedReviews()->attach($review->id);

        $response = $this->actingAs($owner)->delete(
            route('books.destroy', $book)
        );

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    public function test_book_cannot_be_created_with_invalid_field_formats(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'フォーマットテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '978-invalid',
            'published_date' => 'not-a-date',
            'description' => null,
            'image_url' => 'not-a-url',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'isbn',
            'published_date',
            'image_url',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_cannot_be_updated_with_invalid_field_formats_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '元のタイトル',
            'author' => '元の著者',
            'isbn' => '9784000000017',
            'published_date' => '2026-09-23',
            'description' => '元の説明です。',
            'image_url' => null,
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            [
                'title' => '変更後タイトル',
                'author' => '変更後著者',
                'isbn' => '978-invalid',
                'published_date' => 'not-a-date',
                'description' => null,
                'image_url' => 'not-a-url',
                'genres' => [$genre->id],
            ]
        );

        $response->assertSessionHasErrors([
            'isbn',
            'published_date',
            'image_url',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '元のタイトル',
            'author' => '元の著者',
            'isbn' => '9784000000017',
            'published_date' => '2026-09-23',
        ]);
    }

    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(
            route('books.create')
        );

        $response->assertOk();
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });
    }

    public function test_owner_can_view_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)->get(
            route('books.edit', $book)
        );

        $response->assertOk();
        $response->assertViewIs('books.edit');
        $response->assertViewHas('book', function ($viewBook) use ($book, $genre) {
            return $viewBook->is($book)
                && $viewBook->relationLoaded('genres')
                && $viewBook->genres->contains('id', $genre->id);
        });
        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });
    }
}
