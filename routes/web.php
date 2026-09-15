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

Route::resource('books', BookController::class)->only(['index', 'show']);

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
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
});
