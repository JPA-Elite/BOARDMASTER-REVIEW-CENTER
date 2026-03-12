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

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
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
