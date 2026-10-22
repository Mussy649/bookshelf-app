<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    /**
     * Toggle a like on a review and return to the previous page.
     */
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        if ($review->user_id === $request->user()->id) {
            abort(403);
        }

        $request->user()->likedReviews()->toggle($review->id);

        return back();
    }
}
