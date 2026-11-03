<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示
     *
     * @return View 書籍一覧画面
     */
    public function index(): View
    {
        $books = Book::paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録画面を表示
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍登録処理
     *
     * @param  BookRequest  $request  書籍登録情報
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function store(BookRequest $request): RedirectResponse
    {
        $book = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'published_date' => $request->published_date,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'user_id' => auth()->id(),
        ]);

        $book->genres()->sync($request->genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍詳細を表示
     *
     * @param  Book  $book  表示対象の書籍
     * @return View 書籍詳細画面
     */
    public function show(Book $book): View
    {
        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示
     *
     * @param  Book  $book  編集対象の書籍
     * @return View 書籍編集画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍更新処理
     *
     * @param  BookRequest  $request  書籍更新情報
     * @param  Book  $book  更新対象の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $book->update([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'published_date' => $request->published_date,
            'description' => $request->description,
            'image_url' => $request->image_url,
        ]);

        $book->genres()->sync($request->genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * 書籍削除処理
     *
     * @param  Book  $book  削除対象の書籍
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
