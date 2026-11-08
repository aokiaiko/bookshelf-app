<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示
     *
     * @return View　マイ読書レポート画面
     */
    public function index(): View
    {
        $user = Auth::user();
        $reviews = $user->reviews()
            ->with('book.genres')
            ->get();
        $ratingCounts = $reviews->groupBy('rating')
            ->map(fn ($group) => $group->count());

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => 0,
                'average_rating' => $reviews->avg('rating') ?? 0,
            ],
            'rating_distribution' => collect(range(1, 5))
                ->map(fn ($rating) => $ratingCounts->get($rating, 0)),

            'top_rated_books' => $reviews
                ->filter(fn ($review) => $review->rating >= 4)
                ->sortByDesc('rating')
                ->take(5)
                ->map(fn ($review) => [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,

                ]),

            'genre_ratings' => $reviews
                ->flatMap(fn ($review) => $review->book->genres->map(fn ($genre) => [
                    'genre' => $genre,
                    'rating' => $review->rating,
                ])
                )
                ->groupBy(fn ($item) => $item['genre']->id)
                ->map(fn ($group) => [
                    'id' => $group->first()['genre']->id,
                    'name' => $group->first()['genre']->name,
                    'count' => $group->count(),
                    'average_rating' => $group->avg('rating'),
                ])
                ->sortByDesc('average_rating')
                ->take(5)
                ->values(),
        ];

        return view('reports.index', compact('stats'));
    }
}
