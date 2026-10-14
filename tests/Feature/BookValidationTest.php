<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_cannot_be_created_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), []);

        $response->assertSessionHasErrors([
            'title',
            'author',
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
}
