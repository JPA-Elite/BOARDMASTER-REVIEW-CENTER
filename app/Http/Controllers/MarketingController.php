<?php

namespace App\Http\Controllers;

use App\Services\MarketingContentService;
use Inertia\Inertia;
use Inertia\Response;

class MarketingController extends Controller
{
    public function __construct(
        private readonly MarketingContentService $contentService,
    ) {}

    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'faqs' => $this->contentService->homeFaqs(),
            'reviews' => $this->contentService->homeReviews(),
        ]);
    }

    public function partnership(): Response
    {
        return Inertia::render('Partnership');
    }

    public function terms(): Response
    {
        return Inertia::render('TermsOfUse');
    }

    public function privacy(): Response
    {
        return Inertia::render('PrivacyPolicy');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact', [
            'faqs' => $this->contentService->contactFaqs(),
        ]);
    }

    public function visionMission(): Response
    {
        return Inertia::render('VisionMission');
    }
}
