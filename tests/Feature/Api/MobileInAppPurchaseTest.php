<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileInAppPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_plans_endpoint_exposes_payment_provider_and_iap_product_ids(): void
    {
        config()->set('services.mobile_payments.provider', 'in_app_purchase');
        config()->set('services.mobile_payments.in_app_products', [
            'android' => ['plan_a:monthly' => 'ph.boardmaster.plan_a_monthly'],
            'ios' => ['plan_a:monthly' => 'ph.boardmaster.ios.plan_a_monthly'],
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan A Monthly',
            'plan_group' => 'plan_a',
            'group_label' => 'Plan A',
            'sub_plan_label' => '1 month',
            'price' => 299.00,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'duration_days' => 30,
            'sort_order' => 10,
            'description' => 'Monthly premium plan',
            'is_default' => false,
        ]);

        $response = $this->getJson('/api/mobile/plans');

        $response
            ->assertOk()
            ->assertJsonPath('payment_provider', 'in_app_purchase')
            ->assertJsonFragment([
                'id' => $plan->id,
                'payment_provider' => 'in_app_purchase',
                'in_app_product_id_android' => 'ph.boardmaster.plan_a_monthly',
                'in_app_product_id_ios' => 'ph.boardmaster.ios.plan_a_monthly',
            ]);
    }

    public function test_paid_plan_can_be_activated_using_in_app_purchase_endpoint(): void
    {
        config()->set('services.mobile_payments.provider', 'in_app_purchase');
        config()->set('services.mobile_payments.in_app_products', [
            'android' => ['plan_a:monthly' => 'ph.boardmaster.plan_a_monthly'],
            'ios' => [],
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan A Monthly',
            'plan_group' => 'plan_a',
            'group_label' => 'Plan A',
            'sub_plan_label' => '1 month',
            'price' => 299.00,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'duration_days' => 30,
            'sort_order' => 10,
            'description' => 'Monthly premium plan',
            'is_default' => false,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/in-app-purchases/complete', [
            'plan_id' => $plan->id,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'platform' => 'android',
            'product_id' => 'ph.boardmaster.plan_a_monthly',
            'purchase_id' => 'GPA.1234-5678-9012-34567',
            'verification_data' => 'signed-token-value',
            'verification_source' => 'GooglePlay',
            'transaction_date' => now()->getTimestampMs(),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.plan_id', $plan->id)
            ->assertJsonPath('data.tier', 'premium')
            ->assertJsonPath('data.billing_cycle', Plan::BILLING_MONTHLY);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => Plan::BILLING_MONTHLY,
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'provider' => 'in_app_purchase',
            'provider_payment_id' => 'GPA.1234-5678-9012-34567',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'processed',
        ]);
    }

    public function test_in_app_purchase_endpoint_returns_error_when_provider_is_not_enabled(): void
    {
        config()->set('services.mobile_payments.provider', 'paymongo');

        $plan = Plan::query()->create([
            'name' => 'Plan A Monthly',
            'plan_group' => 'plan_a',
            'group_label' => 'Plan A',
            'sub_plan_label' => '1 month',
            'price' => 299.00,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'duration_days' => 30,
            'sort_order' => 10,
            'description' => 'Monthly premium plan',
            'is_default' => false,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/in-app-purchases/complete', [
            'plan_id' => $plan->id,
            'platform' => 'android',
            'product_id' => 'ph.boardmaster.plan_a_monthly',
            'verification_data' => 'signed-token-value',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'In-app purchase is disabled for this environment.',
            ]);
    }
}
