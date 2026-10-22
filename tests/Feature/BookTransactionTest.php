<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookTransactionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Web store()：books INSERT 後に book_genre sync で例外発生
     * → books・book_genre ともにロールバックされることを確認する
     */
    public function test_web_store_rolls_back_book_when_genre_sync_fails(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $syncFailed = false;

        DB::listen(function (QueryExecuted $query) use (&$syncFailed) {
            if (
                ! $syncFailed
                && str_contains($query->sql, 'insert')
                && str_contains($query->sql, 'book_genre')
            ) {
                $syncFailed = true;
                throw new \RuntimeException('Simulated genre sync failure');
            }
        });

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'トランザクションテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000099991',
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $this->assertTrue($syncFailed, 'book_genre への INSERT で例外が発生したことを確認');
        $response->assertStatus(500);
        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    /**
     * Web update()：books UPDATE 後に book_genre sync で例外発生
     * → books の更新と book_genre の変更がともにロールバックされることを確認する
     */
    public function test_web_update_rolls_back_book_and_genres_when_genre_sync_fails(): void
    {
        $owner = User::factory()->create();
        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
        ]);
        $book->genres()->attach($oldGenre->id);

        $syncFailed = false;

        DB::listen(function (QueryExecuted $query) use (&$syncFailed) {
            if (
                ! $syncFailed
                && str_contains($query->sql, 'insert')
                && str_contains($query->sql, 'book_genre')
            ) {
                $syncFailed = true;
                throw new \RuntimeException('Simulated genre sync failure');
            }
        });

        $response = $this->actingAs($owner)->put(route('books.update', $book), [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$newGenre->id],
        ]);

        $this->assertTrue($syncFailed, 'book_genre への INSERT で例外が発生したことを確認');
        $response->assertStatus(500);

        // books の更新がロールバックされ、元のタイトル・著者が維持されていること
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
        ]);

        // 旧ジャンルの紐付けがロールバックで復元されていること
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        // 新ジャンルへの紐付けがロールバックで取り消されていること
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    /**
     * API store()：books INSERT 後に book_genre sync で例外発生
     * → books・book_genre ともにロールバックされることを確認する
     */
    public function test_api_store_rolls_back_book_when_genre_sync_fails(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $syncFailed = false;

        DB::listen(function (QueryExecuted $query) use (&$syncFailed) {
            if (
                ! $syncFailed
                && str_contains($query->sql, 'insert')
                && str_contains($query->sql, 'book_genre')
            ) {
                $syncFailed = true;
                throw new \RuntimeException('Simulated genre sync failure');
            }
        });

        $response = $this->postJson(route('api.v1.books.store'), [
            'title' => 'APIトランザクションテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000099992',
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $this->assertTrue($syncFailed, 'book_genre への INSERT で例外が発生したことを確認');
        $response->assertStatus(500);
        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    /**
     * API update()：books UPDATE 後に book_genre sync で例外発生
     * → books の更新と book_genre の変更がともにロールバックされることを確認する
     */
    public function test_api_update_rolls_back_book_and_genres_when_genre_sync_fails(): void
    {
        $owner = User::factory()->create();
        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => 'API更新前タイトル',
            'author' => 'API更新前著者',
        ]);
        $book->genres()->attach($oldGenre->id);

        Sanctum::actingAs($owner);

        $syncFailed = false;

        DB::listen(function (QueryExecuted $query) use (&$syncFailed) {
            if (
                ! $syncFailed
                && str_contains($query->sql, 'insert')
                && str_contains($query->sql, 'book_genre')
            ) {
                $syncFailed = true;
                throw new \RuntimeException('Simulated genre sync failure');
            }
        });

        $response = $this->putJson(route('api.v1.books.update', $book), [
            'title' => 'API更新後タイトル',
            'author' => 'API更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$newGenre->id],
        ]);

        $this->assertTrue($syncFailed, 'book_genre への INSERT で例外が発生したことを確認');
        $response->assertStatus(500);

        // books の更新がロールバックされ、元のタイトル・著者が維持されていること
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新前タイトル',
            'author' => 'API更新前著者',
        ]);

        // 旧ジャンルの紐付けがロールバックで復元されていること
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        // 新ジャンルへの紐付けがロールバックで取り消されていること
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }
}
