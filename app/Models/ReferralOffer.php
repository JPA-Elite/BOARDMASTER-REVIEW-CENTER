<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralOffer extends Model
{
    protected $fillable = [
        'title',
        'description',
        'points_cost',
        'subject_id',
        'question_limit',
        'access_duration_days',
        'category',
        'brand',
        'image_url',
        'is_active',
        'is_featured',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function isCurrentlyActive(?Carbon $now = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = $now ?? now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
