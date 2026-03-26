<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\AdminNotification;
use App\Models\ReferralRedemption;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('referrals:notify-expired-redemptions', function () {
    $now = Carbon::now();
    $processed = 0;

    ReferralRedemption::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', $now)
        ->whereNull('expired_notified_at')
        ->with(['user:id,name,email', 'offer:id,title'])
        ->orderBy('id')
        ->chunkById(200, function ($rows) use (&$processed, $now): void {
            foreach ($rows as $redemption) {
                $userName = $redemption->user?->name ?? 'User';
                $offerTitle = $redemption->offer?->title ?? 'a reward';
                $expiresAt = $redemption->expires_at
                    ? Carbon::parse($redemption->expires_at)->toDateString()
                    : null;

                AdminNotification::query()->create([
                    'type' => 'referral_redemption_expired',
                    'title' => 'Referral Reward Expired',
                    'message' => $expiresAt
                        ? "{$userName} reward {$offerTitle} expired on {$expiresAt}."
                        : "{$userName} reward {$offerTitle} expired.",
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $redemption->forceFill(['expired_notified_at' => $now])->save();
                $processed++;
            }
        });

    $this->info("Processed {$processed} expired referral redemption(s).");
})->purpose('Create admin notifications for expired referral reward redemptions');
