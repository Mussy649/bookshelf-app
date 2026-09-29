<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $books = $request->user()
            ->favoriteBooks()
            ->with('genres')
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Request $request, Book $book)
    {
        $request->user()->favoriteBooks()->toggle($book->id);

        return back();
    }
}
