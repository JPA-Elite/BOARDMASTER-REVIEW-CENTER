<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->query()
            ->with('referrer:id,name,email')
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('referral_code', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listReferrers(?int $excludeId = null): Collection
    {
        $query = $this->query()
            ->select('id', 'name', 'email')
            ->orderBy('name');

        if ($excludeId) {
            $query->whereKeyNot($excludeId);
        }

        return $query->get();
    }

    public function listAdmins(): Collection
    {
        return $this->query()
            ->select('id', 'name')
            ->where('role', 'admin')
            ->orderBy('name')
            ->get();
    }

    public function countBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        return $this->query()
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    public function countAll(): int
    {
        return $this->query()->count();
    }
}
