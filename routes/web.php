<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/books');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)
        ->except(['index', 'show']);

    Route::resource('genres', GenreController::class);

    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::resource('reviews', ReviewController::class)
        ->only(['edit', 'update', 'destroy']);

    Route::post('/reviews/{review}/likes', [ReviewLikeController::class, 'toggle'])
        ->name('reviews.like');
});

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');
