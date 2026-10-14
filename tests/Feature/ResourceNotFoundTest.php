<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonexistent_book_show_returns_404(): void
    {
        $response = $this->get(
            route('books.show', 999999)
        );

        $response->assertNotFound();
    }

    public function test_authenticated_user_gets_404_when_editing_nonexistent_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            route('books.edit', 999999)
        );

        $response->assertNotFound();
    }

    public function test_authenticated_user_gets_404_when_editing_nonexistent_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            route('reviews.edit', 999999)
        );

        $response->assertNotFound();
    }
}
