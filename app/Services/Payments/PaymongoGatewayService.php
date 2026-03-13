<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymongoGatewayService
{
    public const SUPPORTED_PAYMENT_METHODS = [
        'card',
        'gcash',
        'grab_pay',
        'paymaya',
        'qrph',
    ];

    public function mode(): string
    {
        $mode = (string) config('services.paymongo.mode', 'test');

        return in_array($mode, ['test', 'live'], true) ? $mode : 'test';
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.paymongo.base_url', 'https://api.paymongo.com/v1'), '/');
    }

    public function supportedPaymentMethods(): array
    {
        return self::SUPPORTED_PAYMENT_METHODS;
    }

    public function defaultPaymentMethods(): array
    {
        $configured = (string) config('services.paymongo.default_payment_methods', 'card,gcash');
        $selected = array_filter(array_map('trim', explode(',', $configured)));
        $supported = $this->supportedPaymentMethods();
        $filtered = array_values(array_unique(array_intersect($selected, $supported)));

        return $filtered !== [] ? $filtered : ['card', 'gcash'];
    }

    public function hasTestCredentials(): bool
    {
        return $this->testPublicKey() !== '' && $this->testSecretKey() !== '';
    }

    public function hasLiveCredentials(): bool
    {
        return $this->livePublicKey() !== '' && $this->liveSecretKey() !== '';
    }

    public function hasActiveSecretKey(): bool
    {
        return $this->activeSecretKey() !== '';
    }

    public function hasTestWebhookSecret(): bool
    {
        return $this->testWebhookSecret() !== '';
    }

    public function hasLiveWebhookSecret(): bool
    {
        return $this->liveWebhookSecret() !== '';
    }

    public function hasActiveWebhookSecret(): bool
    {
        return $this->activeWebhookSecret() !== '';
    }

    public function webhookToleranceInSeconds(): int
    {
        return max((int) config('services.paymongo.webhook_tolerance', 300), 0);
    }

    /**
     * @throws RequestException
     */
    public function testConnection(): void
    {
        $this->client()
            ->get('/webhooks')
            ->throw();
    }

    /**
     * @param  array<int, string>  $paymentMethodTypes
     * @param  array<string, scalar|null>  $metadata
     * @return array{id: string, checkout_url: string, status: string|null}
     *
     * @throws RequestException
     */
    public function createCheckoutSession(
        int $amountInCentavos,
        string $itemName,
        string $description,
        array $paymentMethodTypes,
        array $metadata = [],
    ): array {
        if ($amountInCentavos < 1) {
            throw new RuntimeException('Amount should be at least 1 centavo.');
        }

        $methods = array_values(array_unique(array_intersect(
            $paymentMethodTypes,
            $this->supportedPaymentMethods(),
        )));

        if ($methods === []) {
            throw new RuntimeException('Select at least one supported payment method.');
        }

        $attributes = [
            'description' => $description,
            'line_items' => [
                [
                    'amount' => $amountInCentavos,
                    'currency' => 'PHP',
                    'name' => $itemName,
                    'quantity' => 1,
                ],
            ],
            'payment_method_types' => $methods,
            'show_description' => true,
            'show_line_items' => true,
            'success_url' => (string) config('services.paymongo.success_url'),
            'cancel_url' => (string) config('services.paymongo.cancel_url'),
        ];

        $filteredMetadata = array_filter($metadata, static fn ($value) => filled($value));

        if ($filteredMetadata !== []) {
            $attributes['metadata'] = $filteredMetadata;
            $attributes['line_items'][0]['metadata'] = $filteredMetadata;
        }

        $response = $this->client()
            ->post('/checkout_sessions', [
                'data' => [
                    'attributes' => $attributes,
                ],
            ])
            ->throw()
            ->json();

        $data = $response['data'] ?? [];
        $checkoutUrl = $data['attributes']['checkout_url'] ?? null;

        if (! is_string($checkoutUrl) || $checkoutUrl === '') {
            throw new RuntimeException('PayMongo did not return a checkout URL.');
        }

        return [
            'id' => (string) ($data['id'] ?? ''),
            'checkout_url' => $checkoutUrl,
            'status' => $data['attributes']['status'] ?? null,
        ];
    }

    /**
     * @throws RuntimeException
     */
    private function client(): PendingRequest
    {
        $secretKey = $this->activeSecretKey();

        if ($secretKey === '') {
            throw new RuntimeException(sprintf(
                'PayMongo %s secret key is missing. Set it in your .env file first.',
                $this->mode() === 'live' ? 'live' : 'test',
            ));
        }

        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.paymongo.timeout', 30))
            ->withBasicAuth($secretKey, '');
    }

    private function activeSecretKey(): string
    {
        return $this->mode() === 'live'
            ? $this->liveSecretKey()
            : $this->testSecretKey();
    }

    private function activeWebhookSecret(): string
    {
        return $this->mode() === 'live'
            ? $this->liveWebhookSecret()
            : $this->testWebhookSecret();
    }

    private function testPublicKey(): string
    {
        return trim((string) config('services.paymongo.test_public_key', ''));
    }

    private function testSecretKey(): string
    {
        return trim((string) config('services.paymongo.test_secret_key', ''));
    }

    private function livePublicKey(): string
    {
        return trim((string) config('services.paymongo.live_public_key', ''));
    }

    private function liveSecretKey(): string
    {
        return trim((string) config('services.paymongo.live_secret_key', ''));
    }

    private function testWebhookSecret(): string
    {
        return trim((string) config('services.paymongo.test_webhook_secret', ''));
    }

    private function liveWebhookSecret(): string
    {
        return trim((string) config('services.paymongo.live_webhook_secret', ''));
    }

    public function verifyWebhookSignature(
        string $signatureHeader,
        string $rawPayload,
        ?bool $liveMode,
    ): bool {
        $signatureParts = $this->parseSignatureHeader($signatureHeader);
        $timestamp = $signatureParts['t'] ?? null;

        if (! is_int($timestamp) || $timestamp <= 0) {
            return false;
        }

        $tolerance = $this->webhookToleranceInSeconds();

        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $isLive = $liveMode ?? ($this->mode() === 'live');
        $providedSignature = $isLive
            ? ($signatureParts['li'] ?? '')
            : ($signatureParts['te'] ?? '');
        $webhookSecret = $isLive
            ? $this->liveWebhookSecret()
            : $this->testWebhookSecret();

        if ($providedSignature === '' || $webhookSecret === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$rawPayload, $webhookSecret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * @return array{t?: int, te?: string, li?: string}
     */
    private function parseSignatureHeader(string $signatureHeader): array
    {
        $parts = [];

        foreach (explode(',', $signatureHeader) as $item) {
            $pair = explode('=', trim($item), 2);

            if (count($pair) !== 2) {
                continue;
            }

            $key = trim($pair[0]);
            $value = trim($pair[1]);

            if ($key === '' || $value === '') {
                continue;
            }

            if ($key === 't') {
                $parts['t'] = (int) $value;
                continue;
            }

            if (in_array($key, ['te', 'li'], true)) {
                $parts[$key] = $value;
            }
        }

        return $parts;
    }
}
