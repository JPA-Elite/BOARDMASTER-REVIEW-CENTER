<?php

namespace App\Services;

use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MarketingContentService
{
    public function __construct(
        private readonly FaqRepositoryInterface $faqs,
        private readonly ReviewRepositoryInterface $reviews,
    ) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function homeFaqs(): array
    {
        $faqs = $this->faqs->listActive();

        if ($faqs->isEmpty()) {
            $faqs = collect($this->defaultFaqs());
        }

        return $faqs->shuffle()->take(3)->values()->all();
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function homeReviews(): array
    {
        $reviews = $this->reviews->listActive(6);

        if ($reviews->isEmpty()) {
            $reviews = collect($this->defaultReviews());
        }

        return $reviews->shuffle()->take(6)->values()->all();
    }

    /**
     * @return LengthAwarePaginator|array<int, array<string, string>>
     */
    public function contactFaqs(): LengthAwarePaginator|array
    {
        $faqs = $this->faqs->paginateActive(6);

        if ($faqs->total() === 0) {
            return $this->defaultFaqs();
        }

        return $faqs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultFaqs(): array
    {
        return [
            [
                'question' => 'Does BoardMasters focus on Nursing only?',
                'answer' =>
                    'Nursing is a primary focus, but we also support other major Philippine licensure tracks.',
            ],
            [
                'question' => 'Can review centers request demo access?',
                'answer' =>
                    'Yes. Contact us by email so we can discuss a pilot setup based on your student batch size.',
            ],
            [
                'question' => 'Where should we report question issues?',
                'answer' =>
                    'Send the item details and screenshot to support@boardmasters.ph for content review.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultReviews(): array
    {
        return [
            [
                'reviewer_name' => 'Patrick Bat-Og',
                'reviewer_school' => 'Asia Pacific College',
                'quote' =>
                    'BoardMasters is the biggest game changer. It stopped me from spending too much time on topics I already mastered.',
                'rating' => '5',
            ],
            [
                'reviewer_name' => 'Sophie Dizon',
                'reviewer_school' => 'Wesleyan University',
                'quote' =>
                    'The step-by-step explanations made the lessons stick. It finally clicked for me.',
                'rating' => '5',
            ],
            [
                'reviewer_name' => 'Matt Singson',
                'reviewer_school' => 'Ateneo de Naga',
                'quote' =>
                    'Everything is organized in one app. I can practice on the bus and still track my progress.',
                'rating' => '5',
            ],
        ];
    }
}
