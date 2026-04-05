<?php

namespace App\Services\Admin;

use App\Models\Referral;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReferralService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $referrals = $this->referrals->paginateWithRelations($perPage);

        $referrals->through(fn (Referral $referral) => [
            'id' => $referral->id,
            'inviter' => $referral->inviter?->name,
            'inviter_email' => $referral->inviter?->email,
            'invited_user' => $referral->invitedUser?->name,
            'invited_email' => $referral->invitedUser?->email,
            'created_at' => $referral->created_at?->format('M d, Y'),
        ]);

        return $referrals;
    }

    public function create(array $data): Referral
    {
        $referral = $this->referrals->create($data);

        $this->users->query()
            ->whereKey($referral->invited_user_id)
            ->update(['referred_by' => $referral->inviter_id]);

        return $referral;
    }

    public function update(Referral $referral, array $data): Referral
    {
        $referral = $this->referrals->update($referral, $data);

        $this->users->query()
            ->whereKey($referral->invited_user_id)
            ->update(['referred_by' => $referral->inviter_id]);

        return $referral;
    }

    public function delete(Referral $referral): void
    {
        $this->users->query()
            ->whereKey($referral->invited_user_id)
            ->where('referred_by', $referral->inviter_id)
            ->update(['referred_by' => null]);

        $this->referrals->delete($referral);
    }
}
