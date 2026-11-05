<?php

namespace App\Http\Controllers;

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
        return view('reports.index');
    }
}
