<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('genres.store'),
            [
                'name' => 'ファッション',
            ]
        );

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを登録しました。');

        $this->assertDatabaseHas('genres', [
            'name' => 'ファッション',
        ]);
    }

    public function test_genre_cannot_be_created_with_duplicate_name(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->post(
            route('genres.store'),
            [
                'name' => '小説',
            ]
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ファッション',
        ]);

        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => 'ファッション・服飾',
            ]
        );

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ファッション・服飾',
        ]);
    }

    public function test_genre_can_be_updated_without_changing_its_own_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => '小説',
            ]
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_without_books_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('genres.destroy', $genre)
        );

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $response = $this->actingAs($user)->delete(
            route('genres.destroy', $genre)
        );

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas(
            'error',
            '書籍が紐づいているジャンルは削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
