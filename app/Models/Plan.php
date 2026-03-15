<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY = 'yearly';

    protected $fillable = [
        'name',
        'price',
        'billing_cycle',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
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
            self::BILLING_MONTHLY,
            self::BILLING_YEARLY,
        ];
    }

    public function billingLabel(): string
    {
        return $this->billing_cycle === self::BILLING_YEARLY
            ? 'per year'
            : 'per month';
    }
}
