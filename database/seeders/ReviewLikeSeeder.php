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
        $reviews = Review::all();
        $users = User::all();

        foreach ($reviews as $review) {
            $availableUsers = $users->where('id', '!=', $review->user_id);

            $count = rand(0, 3);

            $userIds = $availableUsers->random($count)->pluck('id')->all();

            $review->likedByUsers()->syncWithoutDetaching($userIds);
        }
    }
}
