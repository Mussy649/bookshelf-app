<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
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

    Route::get('/books/isbn/{isbn}', [BookController::class, 'searchByIsbn'])
        ->name('books.isbn');

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

    Route::get('/reports', [MyReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');

    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    Route::post(
        '/reading-plans/{plan}/complete',
        [ReadingPlanController::class, 'complete']
    )->name('reading-plans.complete');

    Route::get(
        '/reading-plans/{plan}/edit',
        [ReadingPlanController::class, 'edit']
    )->name('reading-plans.edit');

    Route::put('/reading-plans/{plan}', [ReadingPlanController::class, 'update'])
        ->name('reading-plans.update');

    Route::delete('/reading-plans/{plan}', [ReadingPlanController::class, 'destroy'])
        ->name('reading-plans.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

});

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');
