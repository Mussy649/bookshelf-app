<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    public function toggle(Request $request, Review $review)
    {
        if ($review->user_id === $request->user()->id) {
            abort(403);
        }

        $request->user()->likedReviews()->toggle($review->id);

        return back();
    }
}
