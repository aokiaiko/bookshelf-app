<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $favorites = [
            1 => [1, 3, 5],
            2 => [2, 4, 6, 8],
            3 => [1, 7, 9],
            4 => [3, 5, 8, 10, 11],
            5 => [2, 6, 10],
        ];

        foreach ($favorites as $userId => $bookIds) {
            $user = User::find($userId);

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }

    }
}
