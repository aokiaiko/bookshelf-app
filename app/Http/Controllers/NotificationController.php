<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧を表示
     *
     * @return View 通知一覧画面
     */
    public function index(): View
    {
        return view('notifications.index');
    }
}
