<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->take(5)->get();
        $reviews = Review::orderBy('id')->get();

        foreach ($reviews as $reviewIndex => $review) {
            $eligibleUsers = $users
                ->where('id', '!=', $review->user_id)
                ->values();

            $likeCount = $reviewIndex % 4;
            $likeUserIds = [];

            for ($i = 0; $i < $likeCount; $i++) {
                $userIndex = ($reviewIndex + $i) % $eligibleUsers->count();
                $likeUserIds[] = $eligibleUsers[$userIndex]->id;
            }

            $review->likedByUsers()
                ->syncWithoutDetaching($likeUserIds);
        }
    }
}
