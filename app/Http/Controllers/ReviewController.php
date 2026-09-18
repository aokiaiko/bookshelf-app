<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Book $book)
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

    public function edit(Review $review)
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    public function update(ReviewRequest $request, Review $review)
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
}
