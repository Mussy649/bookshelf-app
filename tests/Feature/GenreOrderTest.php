<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_genres_are_ordered_oldest_first_even_after_name_update(): void
    {
        $user = User::factory()->create();

        $newGenre = Genre::factory()->create([
            'name' => '新しいジャンル',
            'created_at' => '2026-10-01 10:00:00',
        ]);

        $oldGenre1 = Genre::factory()->create([
            'name' => '古いジャンルA',
            'created_at' => '2026-09-01 10:00:00',
        ]);

        $oldGenre2 = Genre::factory()->create([
            'name' => '古いジャンルB',
            'created_at' => '2026-09-01 10:00:00',
        ]);

        $expectedIds = [
            $oldGenre1->id,
            $oldGenre2->id,
            $newGenre->id,
        ];

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertViewHas('genres', function ($genres) use ($expectedIds) {
                return $genres->pluck('id')->all() === $expectedIds;
            });

        $this->put(route('genres.update', $oldGenre1), [
            'name' => '編集後のジャンル',
        ])->assertRedirect(route('genres.index'));

        $this->get(route('genres.index'))
            ->assertOk()
            ->assertViewHas('genres', function ($genres) use ($expectedIds) {
                return $genres->pluck('id')->all() === $expectedIds;
            });
    }
}
