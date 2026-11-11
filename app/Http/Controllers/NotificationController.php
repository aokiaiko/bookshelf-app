<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知既読処理
     *
     * @param  string  $id  既読対象の通知ID
     * @return RedirectResponse 通知一覧画面へのリダイレクト
     */
    public function update(string $id): RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
