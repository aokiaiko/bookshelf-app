<?php

use App\Http\Controllers\BookController;
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

Route::middleware('auth')->group(function () {
    Route::get('books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('books', [BookController::class, 'store'])->name('books.store');
    Route::get('books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    Route::get('/ranking', function () {
        return 'ランキング画面は未実装です';
    })->name('ranking.index');

    Route::get('/favorites', function () {
        return 'お気に入り画面は未実装です';
    })->name('favorites.index');

    Route::get('/genres', function () {
        return 'ジャンル管理画面は未実装です';
    })->name('genres.index');

    Route::post('/reviews/{review}/like', function () {})->name('reviews.like');

    Route::get('/reviews/{review}/edit', function () {})->name('reviews.edit');

    Route::delete('/reviews/{review}', function () {})->name('reviews.destroy');

    Route::post('/books/{book}/favorite', function () {
        return back();
    })->name('favorites.toggle');

    Route::post('/reviews/store/{book}', function () {
        return back();
    })->name('reviews.store');
});

Route::get('books', [BookController::class, 'index'])->name('books.index');
Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');
Route::redirect('/', '/books');
