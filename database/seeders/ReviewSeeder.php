<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->take(5)->get();
        $books = Book::orderBy('id')->take(11)->get();

        $ratings = [3, 4, 5];

        $comments = [
            '読みやすく、最後まで楽しめました。',
            '内容が分かりやすく、参考になりました。',
            '興味深い内容で、考えるきっかけになりました。',
            '具体例があり、理解しやすかったです。',
            '学びの多い一冊でした。',
            'もう一度読み返したいと思いました。',
            '知らなかったことを多く学べました。',
            '内容に引き込まれ、最後まで一気に読めました。',
        ];

        $reviewIndex = 0;

        foreach ($books as $bookIndex => $book) {
            $reviewCount = $bookIndex === 10 ? 2 : 3;

            for ($i = 0; $i < $reviewCount; $i++) {
                $user = $users[($bookIndex + $i) % $users->count()];

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $ratings[$reviewIndex % count($ratings)],
                    'comment' => $comments[$reviewIndex % count($comments)],
                ]);

                $reviewIndex++;
            }
        }
    }
}
