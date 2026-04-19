<?php

namespace App\Repositories\Eloquent;

use App\Models\Faq;
use App\Repositories\Contracts\FaqRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FaqRepository extends AbstractRepository implements FaqRepositoryInterface
{
    public function __construct(Faq $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->query()
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('question', 'like', "%{$term}%")
                        ->orWhere('answer', 'like', "%{$term}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listActive(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'question', 'answer']);
    }

    public function paginateActive(int $perPage = 6): LengthAwarePaginator
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
