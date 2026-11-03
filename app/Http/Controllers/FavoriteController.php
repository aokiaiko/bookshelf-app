<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入りの登録・解除の切り替え処理
     *
     * @param  Book  $book  対象の書籍
     * @return RedirectResponse 元の画面へのリダイレクト
     */
    public function toggle(Book $book): RedirectResponse
    {
        Auth::user()->favoriteBooks()->toggle($book->id);

        return redirect()->back();
    }

    /**
     * お気に入り一覧を表示
     *
     * @return View お気に入り一覧画面
     */
    public function index(): View
    {
        $books = auth()->user()
            ->favoriteBooks()->paginate(10);

        return view('favorites.index', compact('books'));
    }
}
