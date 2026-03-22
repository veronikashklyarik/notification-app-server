<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Display the notification history for the authenticated user.
     */
    public function index(): View
    {
        $history = auth()->user()
            ->notificationHistory()
            ->with('notification')
            ->latest()
            ->paginate(20);

        return view('history.index', compact('history'));
    }
}
