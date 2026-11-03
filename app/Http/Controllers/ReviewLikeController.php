<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewLikeController extends Controller
{
    /**
     * レビューいいねの登録・解除の切り替え処理
     *
     * @param  Review  $review  対象のレビュー
     * @return RedirectResponse 元の画面へのリダイレクト
     */
    public function toggle(Review $review): RedirectResponse
    {
        Auth::user()->likedReviews()->toggle($review->id);

        return redirect()->back();
    }
}
