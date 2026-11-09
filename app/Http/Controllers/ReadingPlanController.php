<?php

namespace App\Http\Controllers;

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
        return view('reading-plans.create');

    }

    /**
     * 読書計画作成処理
     *
     * @param  Request  $request  読書計画作成情報
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('reading-plans.index');
    }

    /**
     * 読書計画完読処理
     *
     * @param  Request  $request  読書計画登録情報
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function complete(Request $request): RedirectResponse
    {
        return redirect()
            ->route('reading-plans.index');

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
