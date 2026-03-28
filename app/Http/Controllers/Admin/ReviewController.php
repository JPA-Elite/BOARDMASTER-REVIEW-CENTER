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

    public function index(): Response
    {
        $reviews = $this->reviews->paginate();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
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
