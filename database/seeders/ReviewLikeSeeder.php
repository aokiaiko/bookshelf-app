<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviewLikes = [
            1 => [2, 4],
            2 => [5],
            3 => [1, 2, 4],
            4 => [],
            5 => [2, 4],
            6 => [],
            7 => [5],
            8 => [1, 4, 5],
            9 => [2, 3],
            10 => [2],
            11 => [5],
            12 => [1],
            13 => [5],
            14 => [3, 5],
            15 => [2, 4],
            16 => [3],
            17 => [],
            18 => [5],
            19 => [1, 2],
            20 => [2, 3],
            21 => [4],
            22 => [5],
            23 => [4, 5],
            24 => [1, 2],
            25 => [3],
            26 => [],
            27 => [5],
            28 => [],
            29 => [1, 2, 3],
            30 => [1, 4],
            31 => [2],
            32 => [3],
        ];

        foreach ($reviewLikes as $reviewId => $userIds) {
            $review = Review::find($reviewId);

            $review->likedUsers()->syncWithoutDetaching($userIds);
        }
    }
}
