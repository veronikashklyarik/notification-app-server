<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Display all non-pending notification events for the authenticated user.
     */
    public function index(): View
    {
        $events = auth()->user()
            ->notificationEvents()
            ->with('notification')
            ->where('status', '!=', EventStatus::Pending)
            ->orderByDesc('completed_at')
            ->paginate(20);

        return view('history.index', compact('events'));
    }
}
