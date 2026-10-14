<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_cannot_be_updated_to_duplicate_name_and_database_is_unchanged(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        Genre::factory()->create([
            'name' => '技術書',
        ]);

        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => '技術書',
            ]
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_cannot_be_updated_with_invalid_name_and_database_is_unchanged(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => '',
            ]
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(
            route('genres.update', $genre),
            [
                'name' => str_repeat('あ', 256),
            ]
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }
}
