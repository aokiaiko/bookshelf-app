<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(BookIndexRequest $request)
    {
        $bookQuery = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        $genreId = $request->input('genre_id');

        if ($genreId) {
            $bookQuery->whereHas('genres', function ($genreQuery) use ($genreId) {
                $genreQuery->where('genres.id', $genreId);
            });
        }

        $keyword = $request->input('keyword');

        if ($keyword) {
            $bookQuery->where(function ($bookSearchQuery) use ($keyword) {
                $bookSearchQuery->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        $perPage = $request->input('per_page', 20);

        $books = $bookQuery->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
