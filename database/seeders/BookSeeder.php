<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'genre' => ['小説'],
                'description' => '夏目漱石による長編小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'genre' => ['ビジネス', '自己啓発'],
                'description' => '人間関係の原則について解説した書籍。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'genre' => ['技術書'],
                'description' => 'プログラミングコード記述における実践的な手法をまとめた書籍。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',

            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'genre' => ['ビジネス', '自己啓発'],
                'description' => '世界中で読まれ続ける自己啓発本の最高峰とも言える書籍。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4',
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'genre' => ['小説'],
                'description' => '夏目漱石の代表的な中編小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5',
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'genre' => ['歴史', '科学'],
                'description' => '人類の現代に至るプロセスを歴史と科学の視点から描いた世界的名著。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6',
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'genre' => ['技術書'],
                'description' => 'エンジニア向け、プログラミングの保守性を追求するための開発書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7',
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'genre' => ['自己啓発'],
                'description' => 'アドラー心理学を物語形式で分かりやすく解き明かしたベストセラー本。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8',
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'genre' => ['小説'],
                'description' => 'お笑い芸人、又吉直樹が芥川賞受賞したデビュー小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9',
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'genre' => ['ビジネス', '科学'],
                'description' => 'データや事実に基づいて世界を正しくみる習慣を提唱した書籍。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10',
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'genre' => ['ビジネス', '歴史'],
                'description' => '現代社会のインフラの裏側に着目し、世界経済の歴史を描いたビジネスノンフィクション書籍。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11',
            ],
        ];

        $user = User::first();

        foreach ($books as $bookData) {
            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                [
                    'user_id' => $user->id,
                    'title' => $bookData['title'],
                    'author' => $bookData['author'],
                    'published_date' => $bookData['published_date'],
                    'description' => $bookData['description'],
                    'image_url' => $bookData['image_url'],
                ]
            );

            $genreNames = (array) $bookData['genre'];

            $genreIds = Genre::whereIn('name', $genreNames)
                ->pluck('id');

            $book->genres()->sync($genreIds);
        }
    }
}
