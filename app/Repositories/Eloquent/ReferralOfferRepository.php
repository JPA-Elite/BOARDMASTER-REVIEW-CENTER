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

    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->query()
            ->with('subject:id,name')
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('title', 'like', "%{$term}%")
                        ->orWhere('category', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhereHas('subject', function ($subjectQuery) use ($term): void {
                            $subjectQuery->where('name', 'like', "%{$term}%");
                        });
                });
            })
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
