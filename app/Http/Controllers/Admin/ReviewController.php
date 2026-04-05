<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Admin\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews)
    {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 10;
        $reviews = $this->reviews->paginate($search, $perPage);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->reviews->delete($review);

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Review deleted successfully.');
    }

    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->reviews->setActive($review, (bool) $validated['is_active']);

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Review status updated successfully.');
    }
}
