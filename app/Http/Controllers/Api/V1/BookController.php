<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiBookIndexRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * Return a paginated list of books with search and genre filtering.
     */
    public function index(ApiBookIndexRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        /** @var LengthAwarePaginator $books */
        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%")
                        ->orWhere('isbn', 'like', "%{$keyword}%");
                });
            })
            ->when($request->genre, function ($query, $genreId) {
                $query->whereHas('genres', function ($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->latest()
            ->paginate($perPage);

        $books->withQueryString();

        return response()->json($books);
    }

    /**
     * Store a book and its genres in a database transaction.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = DB::transaction(function () use ($request, $validated, $genreIds) {
            $book = $request->user()->books()->create($validated);
            $book->genres()->sync($genreIds);
            $book->load('genres');

            return $book;
        });

        return response()->json($book, 201);
    }

    /**
     * Return the specified book with genres and review information.
     */
    public function show(Book $book): JsonResponse
    {
        $book->load([
            'genres',
            'reviews.user:id,name',
        ]);

        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return response()->json($book);
    }

    /**
     * Update an authorized book and its genres in a transaction.
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds) {
            $book->update($validated);
            $book->genres()->sync($genreIds);
        });

        $book->load('genres');

        return response()->json($book);
    }

    /**
     * Delete an authorized book and return HTTP 204 No Content.
     */
    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
