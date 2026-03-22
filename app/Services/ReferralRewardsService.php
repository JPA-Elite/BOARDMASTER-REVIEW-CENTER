<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralOffer;
use App\Models\ReferralRedemption;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReferralRewardsService
{
    public function pointsPerReferral(): int
    {
        return (int) ReferralSetting::current()->points_per_referral;
    }

    public function earnedPoints(User $user): int
    {
        $count = Referral::query()
            ->where('inviter_id', $user->id)
            ->count();

        return $count * $this->pointsPerReferral();
    }

    public function spentPoints(User $user): int
    {
        return (int) ReferralRedemption::query()
            ->where('user_id', $user->id)
            ->sum('points_spent');
    }

    public function availablePoints(User $user): int
    {
        return max(0, $this->earnedPoints($user) - $this->spentPoints($user));
    }

    /**
     * @return Collection<int, ReferralOffer>
     */
    public function availableOffers(): Collection
    {
        $now = now();

        return ReferralOffer::query()
            ->with('subject:id,name')
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, ReferralRedemption>
     */
    public function activeRedemptions(User $user): Collection
    {
        $now = now();

        return ReferralRedemption::query()
            ->with('offer:id,title,subject_id,question_limit,access_duration_days')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function rewardSubjectLimits(User $user): array
    {
        $limits = [];

        foreach ($this->activeRedemptions($user) as $redemption) {
            $subjectId = $redemption->subject_id;
            if (! $subjectId) {
                continue;
            }

            $limit = (int) ($redemption->question_limit ?? 0);
            if ($limit <= 0) {
                $limit = 0;
            }

            $existing = $limits[$subjectId] ?? 0;
            $limits[$subjectId] = max($existing, $limit);
        }

        return $limits;
    }

    public function redeem(User $user, ReferralOffer $offer): ReferralRedemption
    {
        return DB::transaction(function () use ($user, $offer) {
            $available = $this->availablePoints($user);
            $cost = (int) $offer->points_cost;

            if ($cost > $available) {
                throw new \RuntimeException('Not enough points to redeem this offer.');
            }

            if (! $offer->isCurrentlyActive()) {
                throw new \RuntimeException('This offer is not currently available.');
            }

            $expiresAt = null;
            $duration = (int) ($offer->access_duration_days ?? 0);
            if ($duration > 0) {
                $expiresAt = Carbon::now()->addDays($duration);
            }

            return ReferralRedemption::query()->create([
                'user_id' => $user->id,
                'referral_offer_id' => $offer->id,
                'points_spent' => $cost,
                'subject_id' => $offer->subject_id,
                'question_limit' => $offer->question_limit,
                'expires_at' => $expiresAt,
            ]);
        });
    }
}
