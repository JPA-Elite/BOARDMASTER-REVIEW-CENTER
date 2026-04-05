<?php

namespace App\Repositories\Eloquent;

use App\Models\ReferralOffer;
use App\Repositories\Contracts\ReferralOfferRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReferralOfferRepository extends AbstractRepository implements ReferralOfferRepositoryInterface
{
    public function __construct(ReferralOffer $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->query()
            ->with('subject:id,name')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listActive(): Collection
    {
        return $this->query()
            ->with('subject:id,name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }
}
