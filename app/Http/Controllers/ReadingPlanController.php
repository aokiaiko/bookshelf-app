<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示
     *
     * @param  Request  $request  ステータス絞り込み
     * @return View 読書計画一覧画面
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $query = Auth::user()->readingPlans();

        if ($status) {
            $query->where('status', $status);
        }

        $readingPlans = $query->get();

        return view('reading-plans.index', [
            'readingPlans' => $readingPlans,
            'currentStatus' => $status,
        ]);
    }

    /**
     * 読書計画作成画面を表示
     *
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));

    }

    /**
     * 読書計画作成処理
     *
     * @param  StoreReadingPlanRequest  $request  読書計画作成情報
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        ReadingPlan::create([
            'user_id' => auth()->id(),
            'book_id' => $request->book_id,
            'target_date' => $request->target_date,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画完了処理
     *
     * @param  ReadingPlan  $plan  完了対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index');

    }

    /**
     * 読書計画編集画面を表示
     *
     * @param  ReadingPlan  $plan  編集対象の読書計画
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $plan): View
    {
        $readingPlan = $plan;
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画更新処理
     *
     * @param  UpdateReadingPlanRequest  $request  読書計画更新情報
     * @param  ReadingPlan  $readingPlan  更新対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'target_date' => $request->target_date,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画削除処理
     *
     * @param  Request  $request  読書計画削除情報
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function destroy(Request $request): RedirectResponse
    {
        return redirect()
            ->route('reading-plans.index');

    }
}
