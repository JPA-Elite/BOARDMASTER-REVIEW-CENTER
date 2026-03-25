<?php

namespace App\Models;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_cycle',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    protected static function booted(): void
    {
        static::created(function (UserSubscription $subscription): void {
            $subscription->loadMissing(['user:id,name,email', 'plan:id,name,price,is_default']);

            $plan = $subscription->plan;
            if (! $plan) {
                return;
            }

            if ((float) $plan->price <= 0 || (bool) $plan->is_default) {
                return;
            }

            $userName = $subscription->user?->name ?? 'User';
            AdminNotification::query()->create([
                'type' => 'subscription',
                'title' => 'New Subscription',
                'message' => $userName.' subscribed to '.$plan->name.'.',
                'read_at' => null,
                'created_at' => Carbon::now(),
            ]);
        });
    }
}
