<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_cannot_be_updated_to_duplicate_isbn_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新対象書籍',
            'author' => '更新対象著者',
            'isbn' => '9784000000109',
            'published_date' => '2026-09-01',
        ]);

        Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9784000000116',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            [
                'title' => '変更後タイトル',
                'author' => '変更後著者',
                'isbn' => '9784000000116',
                'published_date' => null,
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新対象書籍',
            'author' => '更新対象著者',
            'isbn' => '9784000000109',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '変更後タイトル',
        ]);
    }

    public function test_book_cannot_be_updated_without_required_title_and_author_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000123',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            [
                'title' => '',
                'author' => '',
                'isbn' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]
        );

        $response->assertSessionHasErrors([
            'title',
            'author',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000123',
        ]);
    }

    public function test_book_cannot_be_updated_without_genre_and_database_is_unchanged(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000130',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            [
                'title' => '変更後タイトル',
                'author' => '変更後著者',
                'isbn' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
                'genres' => [],
            ]
        );

        $response->assertSessionHasErrors('genres');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000130',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }
}
