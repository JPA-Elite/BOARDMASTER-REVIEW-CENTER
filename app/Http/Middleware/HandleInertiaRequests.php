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
        $adminUrl = (string) config('app.admin_url');
        $adminHost = parse_url($adminUrl, PHP_URL_HOST);
        $adminHost = is_string($adminHost) ? strtolower($adminHost) : '';

        if ($user && $user->role === 'admin') {
            $notifications = AdminNotification::query()
                ->latest()
                ->with(['user:id,name,avatar_path'])
                ->limit(50)
                ->get()
                ->map(fn (AdminNotification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'time' => $notification->created_at?->format('M d, Y h:i A'),
                    'read_at' => $notification->read_at?->toDateTimeString(),
                    'user' => $notification->user ? [
                        'id' => $notification->user->id,
                        'name' => $notification->user->name,
                        'avatar_url' => $notification->user->avatar_url,
                    ] : null,
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
            'clientTimeGuard' => [
                'serverUtcNow' => now()->utc()->toIso8601String(),
                'allowedDriftSeconds' => 600,
                'adminHost' => $adminHost,
            ],
            'admin' => $admin,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
