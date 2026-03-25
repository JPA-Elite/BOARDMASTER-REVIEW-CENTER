<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralOffer;
use App\Models\Subject;
use App\Services\Admin\ReferralOfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReferralOfferController extends Controller
{
    public function __construct(private readonly ReferralOfferService $offers)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/ReferralOffers/Index', [
            'offers' => $this->offers->paginate(),
            'settings' => [
                'points_per_referral' => \App\Models\ReferralSetting::current()->points_per_referral,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/ReferralOffers/Form', [
            'mode' => 'create',
            'offer' => null,
            'subjects' => $this->subjects(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOffer($request);
        $this->offers->create($validated);

        return redirect()
            ->route('admin.referral-offers.index')
            ->with('success', 'Referral offer created successfully.');
    }

    public function edit(ReferralOffer $referralOffer): Response
    {
        return Inertia::render('Admin/ReferralOffers/Form', [
            'mode' => 'edit',
            'offer' => [
                'id' => $referralOffer->id,
                'title' => $referralOffer->title,
                'description' => $referralOffer->description,
                'points_cost' => $referralOffer->points_cost,
                'subject_id' => $referralOffer->subject_id,
                'question_limit' => $referralOffer->question_limit,
                'access_duration_days' => $referralOffer->access_duration_days,
                'category' => $referralOffer->category,
                'brand' => $referralOffer->brand,
                'image_url' => $referralOffer->image_url,
                'is_active' => $referralOffer->is_active,
                'is_featured' => $referralOffer->is_featured,
                'sort_order' => $referralOffer->sort_order,
                'starts_at' => $referralOffer->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $referralOffer->ends_at?->format('Y-m-d\TH:i'),
            ],
            'subjects' => $this->subjects(),
        ]);
    }

    public function update(Request $request, ReferralOffer $referralOffer): RedirectResponse
    {
        $validated = $this->validateOffer($request, $referralOffer->id);
        $this->offers->update($referralOffer, $validated);

        return redirect()
            ->route('admin.referral-offers.index')
            ->with('success', 'Referral offer updated successfully.');
    }

    public function destroy(ReferralOffer $referralOffer): RedirectResponse
    {
        $this->offers->delete($referralOffer);

        return redirect()
            ->route('admin.referral-offers.index')
            ->with('success', 'Referral offer deleted successfully.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'points_per_referral' => ['required', 'integer', 'min:1'],
        ]);

        $settings = \App\Models\ReferralSetting::current();
        $settings->update($validated);

        return redirect()
            ->route('admin.referral-offers.index')
            ->with('success', 'Referral settings updated successfully.');
    }

    private function validateOffer(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'points_cost' => ['required', 'integer', 'min:0'],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'question_limit' => ['nullable', 'integer', 'min:1'],
            'access_duration_days' => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    private function subjects(): array
    {
        return Subject::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
            ])
            ->values()
            ->all();
    }
}
