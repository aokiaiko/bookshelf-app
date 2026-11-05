<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('name', '山田太郎')->firstOrFail();
        $user2 = User::where('name', '鈴木花子')->firstOrFail();
        $books = Book::orderBy('id')->take(6)->get();

        $readingPlans = [
            [
                'user_id' => $user1->id,
                'book_id' => $books[0]->id,
                'target_date' => Carbon::today()->addDays(3),
                'status' => 'in_progress',
            ],
            [
                'user_id' => $user1->id,
                'book_id' => $books[1]->id,
                'target_date' => Carbon::today(),
                'status' => 'in_progress',
            ],
            [
                'user_id' => $user1->id,
                'book_id' => $books[2]->id,
                'target_date' => Carbon::today()->subDays(3),
                'status' => 'in_progress',
            ],
            [
                'user_id' => $user1->id,
                'book_id' => $books[3]->id,
                'target_date' => Carbon::today()->addDays(7),
                'status' => 'in_progress',
            ],
            [
                'user_id' => $user1->id,
                'book_id' => $books[4]->id,
                'target_date' => Carbon::today()->subDays(10),
                'status' => 'completed',
                'completed_at' => Carbon::today()->subDays(5),
            ],
            [
                'user_id' => $user2->id,
                'book_id' => $books[5]->id,
                'target_date' => Carbon::today()->addDays(5),
                'status' => 'in_progress',
            ],

        ];

        foreach ($readingPlans as $plan) {
            $plan = ReadingPlan::create($plan);
        }
    }
}
