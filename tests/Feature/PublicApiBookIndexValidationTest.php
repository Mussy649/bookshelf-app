<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiBookIndexValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_validates_per_page_boundaries(): void
    {
        // per_page=1 → 200 OK, per_page が 1
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 1])
        );
        $response
            ->assertStatus(200)
            ->assertJsonPath('per_page', 1);

        // per_page=10 → 200 OK, per_page が 10
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 10])
        );
        $response
            ->assertStatus(200)
            ->assertJsonPath('per_page', 10);

        // per_page=0 → 422, Validation error
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 0])
        );
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page'])
            ->assertJsonPath(
                'errors.per_page.0',
                'ページあたり件数は1以上10以下の整数で指定してください。'
            );

        // per_page=11 → 422, Validation error
        $response = $this->getJson(
            route('api.v1.books.index', ['per_page' => 11])
        );
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page'])
            ->assertJsonPath(
                'errors.per_page.0',
                'ページあたり件数は1以上10以下の整数で指定してください。'
            );
    }

    public function test_public_api_returns_422_for_invalid_genre(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', ['genre' => 99999])
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genre'])
            ->assertJsonPath(
                'errors.genre.0',
                '指定されたジャンルは存在しません。'
            );
    }

    public function test_public_api_returns_422_for_invalid_page(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', ['page' => 0])
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page'])
            ->assertJsonPath(
                'errors.page.0',
                'ページ番号は1以上の整数で指定してください。'
            );
    }

    public function test_public_api_returns_422_when_keyword_exceeds_255_characters(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', ['keyword' => str_repeat('a', 256)])
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['keyword'])
            ->assertJsonPath(
                'errors.keyword.0',
                'キーワードは255文字以内で指定してください。'
            );
    }
}
