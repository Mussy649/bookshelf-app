<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
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

    public function test_authenticated_user_can_create_book_without_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '任意項目なし書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasNoErrors();

        $book = Book::where('title', '任意項目なし書籍')->firstOrFail();

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'isbn' => null,
            'published_date' => null,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
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

    public function test_owner_can_update_book_without_isbn_and_published_date(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9784000000093',
            'published_date' => '2026-09-30',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            [
                'title' => '任意項目なし更新',
                'author' => '更新後著者',
                'isbn' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '任意項目なし更新',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
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
}
