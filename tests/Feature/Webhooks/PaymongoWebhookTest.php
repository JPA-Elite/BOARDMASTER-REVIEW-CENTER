<?php

namespace Tests\Feature\Webhooks;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_checkout_session_payment_id_is_extracted_from_attributes_payments(): void
    {
        config()->set('services.paymongo.test_webhook_secret', 'whsk_test_secret_123');
        config()->set('services.paymongo.webhook_tolerance', 300);

        $user = User::factory()->create();
        $plan = Plan::query()->create([
            'name' => 'Premium Semi-Annual',
            'price' => 1299.00,
            'description' => 'Premium semi annual access',
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_checkout_paid_2',
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_test_456',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'metadata' => [
                                'user_id' => (string) $user->id,
                                'plan_id' => (string) $plan->id,
                                'billing_cycle' => 'semi_annual',
                            ],
                            'line_items' => [
                                [
                                    'amount' => 129900,
                                    'currency' => 'PHP',
                                    'name' => 'Premium Semi Annual Plan',
                                    'quantity' => 1,
                                ],
                            ],
                            'payments' => [
                                [
                                    'id' => 'pay_attr_456',
                                    'type' => 'payment',
                                    'attributes' => [
                                        'amount' => 129900,
                                        'currency' => 'PHP',
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

        $this->assertDatabaseHas('payment_transactions', [
            'provider' => 'paymongo',
            'provider_event_id' => 'evt_checkout_paid_2',
            'provider_checkout_session_id' => 'cs_test_456',
            'provider_payment_id' => 'pay_attr_456',
            'status' => 'processed',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);
    }

    public function test_mobile_subscription_history_includes_provider_payment_id(): void
    {
        config()->set('services.paymongo.test_webhook_secret', 'whsk_test_secret_123');
        config()->set('services.paymongo.webhook_tolerance', 300);

        $user = User::factory()->create();
        $plan = Plan::query()->create([
            'name' => 'Plan B Semi-Annual',
            'plan_group' => 'plan_b',
            'group_label' => 'Plan B',
            'sub_plan_label' => '6 months',
            'price' => 1299.00,
            'billing_cycle' => Plan::BILLING_SEMI_ANNUAL,
            'duration_days' => 183,
            'sort_order' => 20,
            'description' => 'Plan B Semi Annual',
            'is_default' => false,
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_checkout_paid_3',
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_test_789',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'metadata' => [
                                'user_id' => (string) $user->id,
                                'plan_id' => (string) $plan->id,
                                'billing_cycle' => Plan::BILLING_SEMI_ANNUAL,
                            ],
                            'line_items' => [
                                [
                                    'amount' => 129900,
                                    'currency' => 'PHP',
                                    'name' => 'Plan B Semi-Annual',
                                    'quantity' => 1,
                                ],
                            ],
                            'payments' => [
                                [
                                    'id' => 'pay_hist_789',
                                    'type' => 'payment',
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

        $webhookResponse = $this->call(
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

        $webhookResponse->assertOk();

        Sanctum::actingAs($user);

        $historyResponse = $this->getJson('/api/mobile/subscriptions/history');

        $historyResponse
            ->assertOk()
            ->assertJsonPath('data.0.provider_payment_id', 'pay_hist_789');
    }
}
