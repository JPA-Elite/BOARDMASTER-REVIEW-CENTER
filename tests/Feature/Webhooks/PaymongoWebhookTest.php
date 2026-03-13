<?php

namespace Tests\Feature\Webhooks;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_checkout_event_activates_user_subscription(): void
    {
        config()->set('services.paymongo.test_webhook_secret', 'whsk_test_secret_123');
        config()->set('services.paymongo.webhook_tolerance', 300);

        $user = User::factory()->create();
        $plan = Plan::query()->create([
            'name' => 'Premium',
            'price' => 1500.00,
            'description' => 'Premium monthly access',
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_checkout_paid_1',
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_test_123',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'line_items' => [
                                [
                                    'amount' => 150000,
                                    'currency' => 'PHP',
                                    'name' => 'Premium Plan',
                                    'quantity' => 1,
                                    'metadata' => [
                                        'user_id' => (string) $user->id,
                                        'plan_id' => (string) $plan->id,
                                        'reference' => 'INV-1001',
                                    ],
                                ],
                            ],
                        ],
                        'relationships' => [
                            'payments' => [
                                'data' => [
                                    [
                                        'id' => 'pay_test_123',
                                        'type' => 'payment',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawPayload, 'whsk_test_secret_123');

        $response = $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
            ],
            $rawPayload,
        );

        $response->assertOk();

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'provider' => 'paymongo',
            'provider_event_id' => 'evt_checkout_paid_1',
            'provider_checkout_session_id' => 'cs_test_123',
            'provider_payment_id' => 'pay_test_123',
            'status' => 'processed',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'reference' => 'INV-1001',
        ]);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        config()->set('services.paymongo.test_webhook_secret', 'whsk_test_secret_123');
        config()->set('services.paymongo.webhook_tolerance', 300);

        $payload = [
            'data' => [
                'id' => 'evt_invalid_signature',
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_invalid',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'line_items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = time();

        $response = $this->call(
            'POST',
            '/api/webhooks/paymongo',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te=invalidsignature,li=",
            ],
            $rawPayload,
        );

        $response->assertStatus(400);

        $this->assertDatabaseMissing('payment_transactions', [
            'provider_event_id' => 'evt_invalid_signature',
        ]);
    }
}
