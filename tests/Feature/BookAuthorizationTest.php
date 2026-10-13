<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_non_owner_cannot_view_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->get(
            route('books.edit', $book)
        );

        $response->assertForbidden();
    }
}
