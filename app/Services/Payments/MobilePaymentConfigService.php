<?php

namespace App\Services\Payments;

use App\Models\Plan;
use Illuminate\Support\Str;

class MobilePaymentConfigService
{
    public const PROVIDER_PAYMONGO = 'paymongo';
    public const PROVIDER_IN_APP_PURCHASE = 'in_app_purchase';

    /**
     * @return array<int, string>
     */
    public static function providers(): array
    {
        return [
            self::PROVIDER_PAYMONGO,
            self::PROVIDER_IN_APP_PURCHASE,
        ];
    }

    public function provider(): string
    {
        $configured = strtolower(trim((string) config('services.mobile_payments.provider', self::PROVIDER_PAYMONGO)));

        return in_array($configured, self::providers(), true)
            ? $configured
            : self::PROVIDER_PAYMONGO;
    }

    public function isInAppPurchaseEnabled(): bool
    {
        return $this->provider() === self::PROVIDER_IN_APP_PURCHASE;
    }

    public function isPaymongoEnabled(): bool
    {
        return $this->provider() === self::PROVIDER_PAYMONGO;
    }

    /**
     * @return array{provider: string, android: string|null, ios: string|null}
     */
    public function paymentPayloadForPlan(Plan $plan): array
    {
        return [
            'provider' => $this->provider(),
            'android' => $this->resolveInAppProductId($plan, 'android'),
            'ios' => $this->resolveInAppProductId($plan, 'ios'),
        ];
    }

    public function resolveInAppProductId(Plan $plan, string $platform): ?string
    {
        $platformKey = strtolower(trim($platform));
        if (! in_array($platformKey, ['android', 'ios'], true)) {
            return null;
        }

        $catalog = $this->inAppProductCatalog();
        $platformMap = $catalog[$platformKey] ?? null;

        if (! is_array($platformMap) || $platformMap === []) {
            return null;
        }

        foreach ($this->candidateMapKeys($plan) as $key) {
            $value = $platformMap[$key] ?? null;
            if (! is_string($value)) {
                continue;
            }

            $normalized = trim($value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @return array{android: array<string, string>, ios: array<string, string>}
     */
    public function inAppProductCatalog(): array
    {
        $raw = config('services.mobile_payments.in_app_products', '');

        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return ['android' => [], 'ios' => []];
            }

            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return ['android' => [], 'ios' => []];
            }

            $raw = $decoded;
        }

        if (! is_array($raw)) {
            return ['android' => [], 'ios' => []];
        }

        return [
            'android' => $this->normalizePlatformMap($raw['android'] ?? []),
            'ios' => $this->normalizePlatformMap($raw['ios'] ?? []),
        ];
    }

    /**
     * @param  mixed  $map
     * @return array<string, string>
     */
    private function normalizePlatformMap($map): array
    {
        if (! is_array($map)) {
            return [];
        }

        $normalized = [];
        foreach ($map as $key => $value) {
            $mapKey = trim((string) $key);
            $mapValue = trim((string) $value);

            if ($mapKey === '' || $mapValue === '') {
                continue;
            }

            $normalized[strtolower($mapKey)] = $mapValue;
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function candidateMapKeys(Plan $plan): array
    {
        $id = trim((string) $plan->id);
        $group = strtolower(trim($plan->groupKey()));
        $billingCycle = strtolower(trim((string) $plan->billing_cycle));
        $name = strtolower(trim((string) $plan->name));
        $nameSnake = Str::of($name)->replaceMatches('/\s+/', '_')->value();

        $keys = [
            strtolower($id),
            strtolower($id.':'.$billingCycle),
            $group.':'.$billingCycle,
            $group,
            $name,
            strtolower($nameSnake),
        ];

        $filtered = [];
        foreach ($keys as $key) {
            $trimmed = trim($key);
            if ($trimmed !== '') {
                $filtered[] = $trimmed;
            }
        }

        return array_values(array_unique($filtered));
    }
}

