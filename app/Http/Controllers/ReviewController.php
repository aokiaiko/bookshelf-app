<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビュー登録を処理
     *
     * @param  ReviewRequest  $request  レビュー投稿情報
     * @param  Book  $book  レビュー投稿先の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        Review::create([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'user_id' => auth()->id(),
            'book_id' => $book->id,
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビュー編集画面を表示
     *
     * @param  Review  $review  編集対象のレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビュー更新処理
     *
     * @param  ReviewRequest  $request  レビュー更新情報
     * @param  Review  $review  更新対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * レビュー削除処理
     *
     * @param  Review  $review  削除対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを削除しました。');
    }
}
