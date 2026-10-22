<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Display the authenticated user's favorite books.
     */
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->with('genres')
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * Toggle a book's favorite status and return to the previous page.
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $request->user()->favoriteBooks()->toggle($book->id);

        return back();
    }
}
