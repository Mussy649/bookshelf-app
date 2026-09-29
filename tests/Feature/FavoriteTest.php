<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('favorites.toggle', $book)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->post(
            route('favorites.toggle', $book)
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_favorite_own_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post(
            route('favorites.toggle', $book)
        );

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_view_only_own_favorite_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $favoriteBook = Book::factory()->create([
            'title' => '自分のお気に入り本',
        ]);

        $otherFavoriteBook = Book::factory()->create([
            'title' => '他人のお気に入り本',
        ]);

        $user->favoriteBooks()->attach($favoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherFavoriteBook->id);

        $response = $this->actingAs($user)->get(
            route('favorites.index')
        );

        $response->assertOk();
        $response->assertViewIs('favorites.index');
        $response->assertSee('自分のお気に入り本');
        $response->assertDontSee('他人のお気に入り本');
    }

    public function test_favorites_are_paginated_by_ten_and_ordered_by_latest_added(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(11)->create();

        foreach ($books as $index => $book) {
            $favoriteAt = now()->subMinutes(11 - $index);

            $user->favoriteBooks()->attach($book->id, [
                'created_at' => $favoriteAt,
                'updated_at' => $favoriteAt,
            ]);
        }

        $response = $this->actingAs($user)->get(
            route('favorites.index')
        );

        $response->assertOk();

        $response->assertViewHas('books', function ($paginator) use ($books) {
            return $paginator->count() === 10
                && $paginator->total() === 11
                && $paginator->first()->is($books->last());
        });

        $secondPageResponse = $this->actingAs($user)->get(
            route('favorites.index', ['page' => 2])
        );

        $secondPageResponse->assertViewHas('books', function ($paginator) {
            return $paginator->count() === 1
                && $paginator->total() === 11;
        });
    }
}
