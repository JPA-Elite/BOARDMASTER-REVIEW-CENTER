<?php

namespace App\Models;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_id',
        'invited_user_id',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    protected static function booted(): void
    {
        static::created(function (Referral $referral): void {
            $referral->loadMissing([
                'inviter:id,name,email',
                'invitedUser:id,name,email',
            ]);

            $inviterName = $referral->inviter?->name ?? 'User';
            $invitedName = $referral->invitedUser?->name ?? 'a new user';

            AdminNotification::query()->create([
                'user_id' => $referral->inviter_id,
                'type' => 'referral',
                'title' => 'New Referral',
                'message' => $inviterName.' referred '.$invitedName.'.',
                'read_at' => null,
                'created_at' => Carbon::now(),
            ]);
        });
    }
}
