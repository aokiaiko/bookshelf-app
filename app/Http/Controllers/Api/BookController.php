<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Requests\Api\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得
     */
    public function index(BookIndexRequest $request): AnonymousResourceCollection
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
     * 書籍登録処理
     */
    public function store(BookRequest $request): JsonResponse
    {
        $book = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'published_date' => $request->published_date,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'user_id' => $request->user_id,
        ]);

        $book->genres()->sync($request->genres);

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍詳細を取得
     */
    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'reviews.user']);

        return new BookResource($book);
    }

    /**
     * 書籍更新処理
     */
    public function update(BookRequest $request, Book $book): BookResource
    {
        $book->update([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'published_date' => $request->published_date,
            'description' => $request->description,
            'image_url' => $request->image_url,
        ]);

        $book->genres()->sync($request->genres);

        return new BookResource($book);
    }

    /**
     * 書籍削除処理
     */
    public function destroy(Book $book): Response
    {
        $book->delete();

        return response()->noContent();
    }
}
