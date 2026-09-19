<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewLike;
use Illuminate\Support\Facades\Auth;

class ReviewLikeController extends Controller
{
    public function toggle(Review $review)
    {
        if (Auth::user()->likedReviews->contains($review->id)) {
            ReviewLike::where('user_id', auth()->id())
                ->where('review_id', $review->id)
                ->delete();
        } else {
            ReviewLike::firstOrCreate([
                'user_id' => auth()->id(),
                'review_id' => $review->id,
            ]);
        }

        return redirect()->back();
    }
}
