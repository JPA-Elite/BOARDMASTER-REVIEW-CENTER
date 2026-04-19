<?php

namespace App\Services\Admin;

use App\Models\ReferralOffer;
use App\Repositories\Contracts\ReferralOfferRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReferralOfferService
{
    public function __construct(
        private readonly ReferralOfferRepositoryInterface $offers,
    ) {}

    public function paginate(int $perPage = 12, ?string $search = null): LengthAwarePaginator
    {
        return $this->offers->paginate($perPage, $search)->through(
            fn (ReferralOffer $offer) => [
                'id' => $offer->id,
                'title' => $offer->title,
                'points_cost' => $offer->points_cost,
                'subject' => $offer->subject?->name,
                'question_limit' => $offer->question_limit,
                'access_duration_days' => $offer->access_duration_days,
                'category' => $offer->category,
                'brand' => $offer->brand,
                'is_active' => $offer->is_active,
                'is_featured' => $offer->is_featured,
            ],
        );
    }

    public function create(array $data): ReferralOffer
    {
        return $this->offers->create($data);
    }

    public function update(ReferralOffer $offer, array $data): ReferralOffer
    {
        return $this->offers->update($offer, $data);
    }

    public function delete(ReferralOffer $offer): void
    {
        $this->offers->delete($offer);
    }
}
