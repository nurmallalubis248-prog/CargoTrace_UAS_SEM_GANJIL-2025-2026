<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->appNotifications()->with('shipment')->latest()->paginate(15),
        ]);
    }

    /** Polled by the layout every few seconds so admins/customers see new notifications without reloading. */
    public function unread(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(['unread' => $request->user()->appNotifications()->whereNull('read_at')->count()]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
