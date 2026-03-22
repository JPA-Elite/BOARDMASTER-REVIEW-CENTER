<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
