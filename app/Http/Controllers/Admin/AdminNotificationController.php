<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminNotificationController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 25;

        $notifications = AdminNotification::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AdminNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'time' => $notification->created_at?->format('M d, Y h:i A'),
                'read_at' => $notification->read_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'per_page' => $perPage,
            ],
        ]);
    }

    public function destroy(AdminNotification $notification): RedirectResponse
    {
        $notification->delete();

        return back();
    }

    public function clear(): RedirectResponse
    {
        AdminNotification::query()->delete();

        return redirect()->route('admin.notifications.index');
    }

    public function markRead(): Response
    {
        AdminNotification::query()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->noContent();
    }
}
