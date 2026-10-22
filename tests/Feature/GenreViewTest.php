<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreViewTest extends TestCase
{
    use RefreshDatabase;

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
}
