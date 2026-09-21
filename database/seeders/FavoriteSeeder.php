<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->take(5)->get();
        $books = Book::orderBy('id')->take(11)->get();

        $favoriteCounts = [3, 4, 5, 3, 4];

        foreach ($users as $userIndex => $user) {
            $bookIds = [];

            for ($i = 0; $i < $favoriteCounts[$userIndex]; $i++) {
                $bookIndex = ($userIndex + $i) % $books->count();
                $bookIds[] = $books[$bookIndex]->id;
            }

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
