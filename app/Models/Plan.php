<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const BILLING_TRIAL = 'trial';
    public const BILLING_QUARTERLY = 'quarterly';
    public const BILLING_SEMI_ANNUAL = 'semi_annual';
    public const BILLING_ANNUAL = 'annual';
    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY = 'yearly';

    protected $fillable = [
        'name',
        'plan_group',
        'group_label',
        'sub_plan_label',
        'price',
        'billing_cycle',
        'duration_days',
        'sort_order',
        'description',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function subjectAccesses(): HasMany
    {
        return $this->hasMany(PlanSubjectAccess::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * @return array<int, string>
     */
    public static function billingCycles(): array
    {
        return [
            self::BILLING_TRIAL,
            self::BILLING_QUARTERLY,
            self::BILLING_SEMI_ANNUAL,
            self::BILLING_ANNUAL,
            self::BILLING_MONTHLY,
            self::BILLING_YEARLY,
        ];
    }

    public function billingLabel(): string
    {
        return match ($this->billing_cycle) {
            self::BILLING_TRIAL => '3 days',
            self::BILLING_QUARTERLY => '3 months',
            self::BILLING_SEMI_ANNUAL => '6 months',
            self::BILLING_ANNUAL, self::BILLING_YEARLY => '12 months',
            self::BILLING_MONTHLY => '1 month',
            default => 'Per cycle',
        };
    }

    public function durationDays(): int
    {
        if ((int) ($this->duration_days ?? 0) > 0) {
            return (int) $this->duration_days;
        }

        return self::durationDaysForCycle($this->billing_cycle);
    }

    public static function durationDaysForCycle(?string $billingCycle): int
    {
        return match ($billingCycle) {
            self::BILLING_TRIAL => 3,
            self::BILLING_QUARTERLY => 90,
            self::BILLING_SEMI_ANNUAL => 180,
            self::BILLING_ANNUAL, self::BILLING_YEARLY => 365,
            self::BILLING_MONTHLY => 30,
            default => 30,
        };
    }

    public function groupKey(): string
    {
        $explicit = trim((string) $this->plan_group);
        if ($explicit !== '') {
            return $explicit;
        }

        $normalized = strtolower(trim($this->name));
        if ($this->isFreeTrial()) {
            return 'free_trial';
        }

        if (str_contains($normalized, 'plan b')
            || str_contains($normalized, 'mock board')) {
            return 'plan_b';
        }

        return 'plan_a';
    }

    public function groupTitle(): string
    {
        $explicit = trim((string) $this->group_label);
        if ($explicit !== '') {
            return $explicit;
        }

        return match ($this->groupKey()) {
            'free_trial' => 'Free Trial',
            'plan_b' => 'Plan B: Mock Board Exam + All Nursing Concepts',
            'plan_a' => 'Plan A: All Nursing Concepts',
            default => ucwords(str_replace('_', ' ', $this->groupKey())),
        };
    }

    public function subPlanTitle(): string
    {
        $explicit = trim((string) $this->sub_plan_label);
        if ($explicit !== '') {
            return $explicit;
        }

        if ($this->isFreeTrial()) {
            return '3 days FREE';
        }

        return $this->billingLabel();
    }

    public function isFreeTrial(): bool
    {
        $normalized = strtolower(trim($this->name));

        return (bool) $this->is_default
            || (float) $this->price <= 0
            || $this->billing_cycle === self::BILLING_TRIAL
            || $this->plan_group === 'free_trial'
            || str_contains($normalized, 'free')
            || str_contains($normalized, 'trial');
    }
}
