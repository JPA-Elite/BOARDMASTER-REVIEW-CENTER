<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $fillable = [
        'points_per_referral',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'points_per_referral' => 10,
        ]);
    }
}
