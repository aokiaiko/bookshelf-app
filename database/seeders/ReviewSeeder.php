<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = [
            [
                'user_id' => 1,
                'book_id' => 1,
                'rating' => 5,
                'comment' => '猫の視点から人間社会を描いているところが面白かったです。',
            ],
            [
                'user_id' => 2,
                'book_id' => 1,
                'rating' => 4,
                'comment' => '独特な語り口が印象に残りました。',
            ],
            [
                'user_id' => 3,
                'book_id' => 1,
                'rating' => 3,
                'comment' => '独特な表現が多く、少し難しいところもありました。',
            ],
            [
                'user_id' => 4,
                'book_id' => 2,
                'rating' => 4,
                'comment' => '人との接し方について改めて考えるきっかけになりました。',
            ],
            [
                'user_id' => 5,
                'book_id' => 2,
                'rating' => 4,
                'comment' => '仕事でのコミュニケーションにも活かせそうです。',
            ],
            [
                'user_id' => 1,
                'book_id' => 2,
                'rating' => 3,
                'comment' => '基本的な考え方を整理するのに役立ちました。',
            ],
            [
                'user_id' => 2,
                'book_id' => 3,
                'rating' => 5,
                'comment' => '読みやすいコードを書くための考え方が具体的で参考になりました。',
            ],
            [
                'user_id' => 3,
                'book_id' => 3,
                'rating' => 5,
                'comment' => '実際の開発ですぐに活用できる内容が多かったです。',
            ],
            [
                'user_id' => 4,
                'book_id' => 3,
                'rating' => 4,
                'comment' => '具体例が分かりやすく、実践しやすいと感じました。',
            ],
            [
                'user_id' => 5,
                'book_id' => 4,
                'rating' => 3,
                'comment' => '参考になる部分が多いですが、少し難しく感じるところもありました。',
            ],
            [
                'user_id' => 1,
                'book_id' => 4,
                'rating' => 4,
                'comment' => '日々の行動を見直すきっかけになる内容でした。',
            ],
            [
                'user_id' => 2,
                'book_id' => 4,
                'rating' => 4,
                'comment' => '自分の生活に取り入れてみたい考え方がありました。',
            ],
            [
                'user_id' => 3,
                'book_id' => 5,
                'rating' => 5,
                'comment' => 'テンポが良く、登場人物も印象的で読みやすかったです。',
            ],
            [
                'user_id' => 4,
                'book_id' => 5,
                'rating' => 4,
                'comment' => '時代を感じながらも楽しく読める作品でした。',
            ],
            [
                'user_id' => 5,
                'book_id' => 5,
                'rating' => 3,
                'comment' => '有名な作品ですが、自分には少し読みづらく感じました。',
            ],
            [
                'user_id' => 1,
                'book_id' => 6,
                'rating' => 5,
                'comment' => '人類の歴史を幅広い視点から考えられる一冊でした。',
            ],
            [
                'user_id' => 2,
                'book_id' => 6,
                'rating' => 3,
                'comment' => '内容は濃いですが、読み終えるまでに少し時間がかかりました。',
            ],
            [
                'user_id' => 3,
                'book_id' => 6,
                'rating' => 5,
                'comment' => '歴史をこれまでとは違った視点で見ることができました。',
            ],
            [
                'user_id' => 4,
                'book_id' => 7,
                'rating' => 5,
                'comment' => '保守しやすいコードについて深く考えることができました。',
            ],
            [
                'user_id' => 5,
                'book_id' => 7,
                'rating' => 4,
                'comment' => 'コードの品質を意識するために役立つ内容でした。',
            ],
            [
                'user_id' => 1,
                'book_id' => 8,
                'rating' => 4,
                'comment' => '対話形式なので難しいテーマでも読み進めやすかったです。',
            ],
            [
                'user_id' => 2,
                'book_id' => 8,
                'rating' => 3,
                'comment' => '興味深い内容でしたが、自分には少し合わない部分もありました。',
            ],
            [
                'user_id' => 3,
                'book_id' => 8,
                'rating' => 5,
                'comment' => '人間関係について新しい視点を得ることができました。',
            ],
            [
                'user_id' => 4,
                'book_id' => 9,
                'rating' => 5,
                'comment' => '芸人の世界や人間関係が丁寧に描かれていて引き込まれました。',
            ],
            [
                'user_id' => 5,
                'book_id' => 9,
                'rating' => 5,
                'comment' => '登場人物の葛藤が伝わってきて印象に残りました。',
            ],
            [
                'user_id' => 1,
                'book_id' => 9,
                'rating' => 3,
                'comment' => '文章は読みやすかったですが、好みとは少し違いました。',
            ],
            [
                'user_id' => 2,
                'book_id' => 10,
                'rating' => 5,
                'comment' => '自分の世界の見方を見直すきっかけになりました。',
            ],
            [
                'user_id' => 3,
                'book_id' => 10,
                'rating' => 4,
                'comment' => '具体的なデータが多く、説得力のある内容でした。',
            ],
            [
                'user_id' => 4,
                'book_id' => 10,
                'rating' => 4,
                'comment' => 'データをもとに物事を見る重要性を改めて感じました。',
            ],
            [
                'user_id' => 5,
                'book_id' => 11,
                'rating' => 3,
                'comment' => '物流と世界経済の関係を知ることができました。',
            ],
            [
                'user_id' => 1,
                'book_id' => 11,
                'rating' => 4,
                'comment' => '普段意識しない物流の仕組みを知れて面白かったです。',
            ],
            [
                'user_id' => 2,
                'book_id' => 11,
                'rating' => 4,
                'comment' => 'コンテナが社会に与えた影響の大きさに驚きました。',
            ],
        ];

        foreach ($reviews as $review) {
            Review::create($review);
        }
    }
}
