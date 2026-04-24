<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class AdminNotificationController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 25;

        $notifications = AdminNotification::query()
            ->latest()
            ->with(['user:id,name,avatar_path', 'plan:id,name,price,billing_cycle'])
            ->paginate($perPage)
            ->withQueryString();

        $this->attachPaymentMeta($notifications->getCollection());

        $notifications->through(fn (AdminNotification $notification) => $this->toNotificationRow($notification));

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
        @set_time_limit(0);

        try {
            AdminNotification::query()->truncate();

            return redirect()
                ->route('admin.notifications.index')
                ->with('success', 'All notifications cleared.');
        } catch (Throwable) {
            // Fall back to batched deletes when truncate is blocked by constraints/permissions.
        }

        $batchSize = 100000;
        $totalDeleted = 0;

        while (true) {
            $ids = AdminNotification::query()
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $lastId = $ids->last();
            if (! is_numeric($lastId)) {
                break;
            }

            $deleted = AdminNotification::query()
                ->where('id', '<=', (int) $lastId)
                ->delete();

            $totalDeleted += (int) $deleted;

            if ($deleted < 1) {
                break;
            }
        }

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', "Deleted {$totalDeleted} notification(s).");
    }

    public function markRead(): Response
    {
        AdminNotification::query()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->noContent();
    }

    private function toNotificationRow(AdminNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'time' => $notification->created_at?->format('M d, Y h:i A'),
            'read_at' => $notification->read_at?->toDateTimeString(),
            'user' => $notification->user ? [
                'id' => $notification->user->id,
                'name' => $notification->user->name,
                'avatar_url' => $notification->user->avatar_url,
            ] : null,
            'payment' => $notification->payment_meta ?? null,
        ];
    }

    /**
     * Attach best-effort payment details to subscription-related notifications.
     *
     * @param  \Illuminate\Support\Collection<int, AdminNotification>  $collection
     */
    private function attachPaymentMeta($collection): void
    {
        $subscriptionNotifications = $collection
            ->filter(function (AdminNotification $notification): bool {
                if (! is_string($notification->type) || $notification->type === '') {
                    return false;
                }

                return str_starts_with($notification->type, 'subscription');
            })
            ->values();

        if ($subscriptionNotifications->isEmpty()) {
            return;
        }

        $userIds = $subscriptionNotifications
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $planIds = $subscriptionNotifications
            ->pluck('plan_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($userIds === [] || $planIds === []) {
            return;
        }

        $minDate = null;
        $maxDate = null;
        foreach ($subscriptionNotifications as $notification) {
            $createdAt = $notification->created_at;
            if (! $createdAt) {
                continue;
            }

            $minDate = $minDate === null || $createdAt->lt($minDate) ? $createdAt : $minDate;
            $maxDate = $maxDate === null || $createdAt->gt($maxDate) ? $createdAt : $maxDate;
        }

        if ($minDate === null || $maxDate === null) {
            return;
        }

        $transactions = PaymentTransaction::query()
            ->whereIn('provider', ['paymongo', 'in_app_purchase'])
            ->whereIn('user_id', $userIds)
            ->whereIn('plan_id', $planIds)
            ->where('status', 'processed')
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [
                $minDate->copy()->startOfDay(),
                $maxDate->copy()->endOfDay(),
            ])
            ->orderByDesc('processed_at')
            ->get(['provider', 'user_id', 'plan_id', 'processed_at', 'raw_payload', 'provider_payment_id']);

        $entriesByKey = [];
        foreach ($transactions as $transaction) {
            $dateKey = $transaction->processed_at?->toDateString();
            if (! $dateKey) {
                continue;
            }

            $userId = $transaction->user_id;
            $planId = $transaction->plan_id;

            if (! $userId || ! $planId) {
                continue;
            }

            $key = $userId.'|'.$planId.'|'.$dateKey;
            $providerPaymentId = is_string($transaction->provider_payment_id) && $transaction->provider_payment_id !== ''
                ? $transaction->provider_payment_id
                : null;
            $method = $this->extractMethodLabel($transaction->provider, $transaction->raw_payload);

            if ($method === null && $providerPaymentId === null) {
                continue;
            }

            $entriesByKey[$key] ??= [];
            $entriesByKey[$key][] = [
                'method' => $method,
                'payment_id' => $providerPaymentId,
            ];
        }

        foreach ($subscriptionNotifications as $notification) {
            $createdAt = $notification->created_at;
            if (! $createdAt) {
                continue;
            }

            if (! $notification->user_id || ! $notification->plan_id) {
                continue;
            }

            $key = $notification->user_id.'|'.$notification->plan_id.'|'.$createdAt->toDateString();
            $entry = $entriesByKey[$key][0] ?? null;
            if (! is_array($entry)) {
                continue;
            }

            $notification->payment_meta = $entry;
        }
    }

    /**
     * Best-effort scan of payment payload for known method identifiers.
     *
     * @param  array<string, mixed>|null  $payload
     */
    private function extractMethodLabel(?string $provider, ?array $payload): ?string
    {
        if ($provider === 'in_app_purchase') {
            $platform = is_array($payload)
                ? strtolower(trim((string) ($payload['platform'] ?? '')))
                : '';

            return match ($platform) {
                'android' => 'Google Play',
                'ios' => 'Apple App Store',
                default => 'In-App Purchase',
            };
        }

        if (! is_array($payload) || $payload === []) {
            return null;
        }

        $supported = ['card', 'gcash', 'grab_pay', 'paymaya', 'qrph'];
        $found = $this->findFirstSupportedMethod($payload, $supported);
        if (! is_string($found) || $found === '') {
            return null;
        }

        return match (strtolower($found)) {
            'gcash' => 'GCash',
            'card' => 'Card',
            'grab_pay' => 'GrabPay',
            'paymaya' => 'PayMaya',
            'qrph' => 'QRPh',
            default => strtoupper($found),
        };
    }

    /**
     * @param  mixed  $value
     * @param  array<int, string>  $supported
     */
    private function findFirstSupportedMethod($value, array $supported): ?string
    {
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, $supported, true) ? $normalized : null;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $found = $this->findFirstSupportedMethod($item, $supported);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
