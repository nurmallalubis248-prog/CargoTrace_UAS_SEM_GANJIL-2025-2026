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
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['unread', 'read'], true) ? $request->query('tab') : 'all';

        $notifications = $user->appNotifications()
            ->with(['shipment.origin', 'shipment.destination'])
            ->when($tab === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($tab === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->latest()->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $unread = $user->appNotifications()->whereNull('read_at')->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'tab' => $tab,
            'counts' => [
                'all' => $user->appNotifications()->count(),
                'unread' => $unread,
                'read' => $user->appNotifications()->whereNotNull('read_at')->count(),
            ],
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
