<?php

namespace App\Repositories\Eloquent;

use App\Models\Referral;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReferralRepository extends AbstractRepository implements ReferralRepositoryInterface
{
    public function __construct(Referral $model)
    {
        parent::__construct($model);
    }

    public function paginateWithRelations(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->query()
            ->with(['inviter:id,name,email', 'invitedUser:id,name,email'])
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->whereHas('inviter', function ($userQuery) use ($term): void {
                        $userQuery->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    })->orWhereHas('invitedUser', function ($userQuery) use ($term): void {
                        $userQuery->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function syncForInvited(int $invitedUserId, ?int $inviterId): void
    {
        if (! $inviterId) {
            $this->query()
                ->where('invited_user_id', $invitedUserId)
                ->delete();

            return;
        }

        $this->query()->updateOrCreate(
            ['invited_user_id' => $invitedUserId],
            ['inviter_id' => $inviterId]
        );
    }
}
