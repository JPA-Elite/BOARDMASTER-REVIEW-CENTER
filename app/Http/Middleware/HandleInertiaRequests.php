<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\AdminNotification;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $admin = null;
        $user = $request->user();

        if ($user && $user->role === 'admin') {
            $notifications = AdminNotification::query()
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (AdminNotification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'time' => $notification->created_at?->format('M d, Y h:i A'),
                    'read_at' => $notification->read_at?->toDateTimeString(),
                ]);

            $admin = [
                'notificationCount' => AdminNotification::query()->whereNull('read_at')->count(),
                'notifications' => $notifications,
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'admin' => $admin,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
