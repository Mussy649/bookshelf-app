<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
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
        $today = Carbon::today();

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::whereIn('isbn', [
            '9784101010014',
            '9784422100524',
            '9784873115658',
            '9784863940246',
            '9784101010021',
            '9784309226712',
        ])->get()->keyBy('isbn');

        $plans = [
            [
                'user_id' => $yamada->id,
                'book_id' => $books['9784101010014']->id,
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books['9784422100524']->id,
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books['9784873115658']->id,
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books['9784863940246']->id,
                'target_date' => $today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books['9784101010021']->id,
                'target_date' => $today->copy()->subDays(10),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy()->subDays(5),
            ],
            [
                'user_id' => $suzuki->id,
                'book_id' => $books['9784309226712']->id,
                'target_date' => $today->copy()->addDays(5),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
        ];

        foreach ($plans as $plan) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $plan['user_id'],
                    'book_id' => $plan['book_id'],
                ],
                $plan
            );
        }
    }
}
