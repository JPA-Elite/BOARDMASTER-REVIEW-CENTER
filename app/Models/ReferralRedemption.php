<?php

namespace App\Models;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReferralRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'referral_offer_id',
        'points_spent',
        'subject_id',
        'question_limit',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ReferralOffer::class, 'referral_offer_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function isActive(): bool
    {
        if (! $this->expires_at) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    protected static function booted(): void
    {
        static::created(function (ReferralRedemption $redemption): void {
            $redemption->loadMissing([
                'user:id,name,email',
                'offer:id,title',
            ]);

            $userName = $redemption->user?->name ?? 'User';
            $offerTitle = $redemption->offer?->title ?? 'a reward';
            $pointsSpent = (int) ($redemption->points_spent ?? 0);

            AdminNotification::query()->create([
                'type' => 'referral_redemption',
                'title' => 'Referral Reward Redeemed',
                'message' => $userName.' redeemed '.$offerTitle.
                    ($pointsSpent > 0 ? " using {$pointsSpent} points." : '.'),
                'read_at' => null,
                'created_at' => Carbon::now(),
            ]);
        });
    }
}
