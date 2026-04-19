<?php

namespace App\Services\Admin;

use App\Models\Faq;
use App\Repositories\Contracts\FaqRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FaqService
{
    public function __construct(private readonly FaqRepositoryInterface $faqs)
    {
    }

    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $faqs = $this->faqs->paginate($perPage, $search);

        $faqs->through(fn (Faq $faq) => [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'is_active' => $faq->is_active,
            'sort_order' => $faq->sort_order,
            'created_at' => $faq->created_at?->format('M d, Y'),
        ]);

        return $faqs;
    }

    public function create(array $data): Faq
    {
        return $this->faqs->create([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'is_active' => $data['is_active'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(Faq $faq, array $data): Faq
    {
        return $this->faqs->update($faq, [
            'question' => $data['question'],
            'answer' => $data['answer'],
            'is_active' => $data['is_active'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function delete(Faq $faq): void
    {
        $this->faqs->delete($faq);
    }
}
