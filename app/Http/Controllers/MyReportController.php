<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MyReportController extends Controller
{
    /**
     * Display the authenticated user's reading report.
     */
    public function index(Request $request): View
    {
        $reviews = $request->user()
            ->reviews()
            ->with('book.genres')
            ->get();

        $ratingDistribution = collect(range(1, 5))
            ->map(
                fn (int $rating): int => $reviews
                    ->where('rating', $rating)
                    ->count()
            );

        $topRatedBooks = $reviews
            ->filter(
                fn (Review $review): bool => $review->rating >= 4
            )
            ->sortByDesc('rating')
            ->unique('book_id')
            ->take(5)
            ->map(
                fn (Review $review): array => [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => (int) $review->rating,
                ]
            )
            ->values();

        $genreRatings = $reviews
            ->flatMap(
                fn (Review $review): Collection => $review->book->genres
                    ->map(
                        fn (Genre $genre): array => [
                            'id' => $genre->id,
                            'name' => $genre->name,
                            'rating' => (int) $review->rating,
                        ]
                    )
            )
            ->groupBy('id')
            ->map(function (Collection $items): array {
                $genre = $items->first();

                return [
                    'id' => $genre['id'],
                    'name' => $genre['name'],
                    'count' => $items->count(),
                    'average_rating' => (float) $items->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews
                    ->pluck('book_id')
                    ->unique()
                    ->count(),
                'average_rating' => (float) ($reviews->avg('rating') ?? 0),
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
