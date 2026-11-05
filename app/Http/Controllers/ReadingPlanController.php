<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示
     *
     * @return View 読書計画一覧画面
     */
    public function index(): View
    {
        return view('reading-plans.index', [
            'readingPlans' => collect(),
            'currentStatus' => null,
        ]);
    }
}
