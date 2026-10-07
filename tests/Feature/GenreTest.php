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

    public function test_authenticated_user_can_view_genre_index(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->get(
            route('genres.index')
        );

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });
        $response->assertSee('小説');
    }

    public function test_authenticated_user_can_view_genre_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            route('genres.create')
        );

        $response->assertOk();
        $response->assertViewIs('genres.create');
    }

    public function test_authenticated_user_can_view_genre_show(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $books = Book::factory()->count(11)->create();

        foreach ($books as $book) {
            $genre->books()->attach($book->id);
        }

        $response = $this->actingAs($user)->get(
            route('genres.show', $genre)
        );

        $response->assertOk();
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre', $genre);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11;
        });
    }

    public function test_authenticated_user_can_view_genre_edit_form(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $response = $this->actingAs($user)->get(
            route('genres.edit', $genre)
        );

        $response->assertOk();
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre', $genre);
        $response->assertSee('ビジネス');
    }

    public function test_genre_index_displays_empty_state_when_no_genres_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            route('genres.index')
        );

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertSee('ジャンルが登録されていません。');
    }

    public function test_genre_show_displays_empty_state_when_genre_has_no_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(
            route('genres.show', $genre)
        );

        $response->assertOk();
        $response->assertViewIs('genres.show');
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }

    public function test_genre_cannot_be_created_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('genres.store'),
            ['name' => '']
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_cannot_be_created_when_name_exceeds_255_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('genres.store'),
            ['name' => str_repeat('あ', 256)]
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }
}
