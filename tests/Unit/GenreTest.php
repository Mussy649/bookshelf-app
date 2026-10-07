<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID3-1: Genre → Books
     * books() belongsToMany（中間テーブル: book_genre）経由で、
     * そのジャンルに紐づいた書籍を取得できる。
     */
    public function test_genre_belongs_to_many_books(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $this->assertTrue($genre->books->contains($book));
        $this->assertCount(1, $genre->books);
    }
}
