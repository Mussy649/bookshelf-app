<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BookIsbnLookupService
{
    public function search(string $isbn): array
    {
        try {
            $response = Http::get(
                config('services.google_books.url'),
                [
                    'q' => "isbn:{$isbn}",
                    'key' => config('services.google_books.key'),
                ]
            );
        } catch (ConnectionException $e) {
            return [
                'status' => 502,
                'error' => '書籍情報の取得に失敗しました。',
            ];
        }

        if ($response->failed()) {
            return [
                'status' => 502,
                'error' => '書籍情報の取得に失敗しました。',
            ];
        }

        if ($response->json('totalItems', 0) === 0) {
            $book = $this->searchOpenLibrary($isbn);

            if ($book === null) {
                try {
                    $book = $this->searchOpenBd($isbn);
                } catch (ConnectionException $e) {
                    return [
                        'status' => 502,
                        'error' => '書籍情報の取得に失敗しました。',
                    ];
                }
            }

            if ($book !== null) {
                return [
                    'status' => 200,
                    'data' => $book,
                ];
            }

            return [
                'status' => 404,
                'error' => '該当する書籍が見つかりませんでした。',
            ];
        }

        $volumeInfo = $response->json('items.0.volumeInfo', []);

        return [
            'status' => 200,
            'data' => [
                'title' => $volumeInfo['title'] ?? null,
                'author' => isset($volumeInfo['authors'])
                    ? implode(', ', $volumeInfo['authors'])
                    : null,
                'isbn' => $isbn,
                'published_date' => $volumeInfo['publishedDate'] ?? null,
                'description' => $volumeInfo['description'] ?? null,
                'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            ],
        ];
    }

    private function searchOpenLibrary(string $isbn): ?array
    {
        try {
            $response = Http::timeout(5)->get(
                "https://openlibrary.org/isbn/{$isbn}.json"
            );
        } catch (ConnectionException $e) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $isbnList = $response->json('isbn_13', []);

        if (! in_array($isbn, $isbnList, true)) {
            return null;
        }

        $title = $response->json('title');
        $authorKey = $response->json('authors.0.key');

        if (! $title || ! $authorKey) {
            return null;
        }

        try {
            $authorResponse = Http::timeout(5)->get(
                'https://openlibrary.org'.$authorKey.'.json'
            );
        } catch (ConnectionException $e) {
            return null;
        }

        if ($authorResponse->failed()) {
            return null;
        }

        $author = $authorResponse->json('name');

        if (! $author) {
            return null;
        }

        $publishDate = $response->json('publish_date');
        $publishedDate = null;

        if ($publishDate) {
            try {
                $publishedDate = Carbon::parse($publishDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $publishedDate = null;
            }
        }

        $coverId = collect($response->json('covers', []))
            ->first(fn ($id) => is_numeric($id) && $id > 0);

        return [
            'title' => $title,
            'author' => $author,
            'isbn' => $isbn,
            'published_date' => $publishedDate,
            'description' => null,
            'image_url' => $coverId
                ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg"
                : null,
        ];
    }

    private function searchOpenBd(string $isbn): ?array
    {
        $response = Http::timeout(5)->get(
            'https://api.openbd.jp/v1/get',
            ['isbn' => $isbn]
        );

        if ($response->failed()) {
            return null;
        }

        $book = $response->json('0.summary');

        if (! is_array($book) || ($book['isbn'] ?? null) !== $isbn) {
            return null;
        }

        if (empty($book['title']) || empty($book['author'])) {
            return null;
        }

        return [
            'title' => $book['title'],
            'author' => $book['author'],
            'isbn' => $isbn,
            'published_date' => null,
            'description' => null,
            'image_url' => ($book['cover'] ?? null) ?: null,
        ];
    }
}
