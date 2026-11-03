<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'book_id',
    ];

    /**
     * お気に入りとユーザーのリレーションを定義
     *
     * @return BelongsTo お気に入に紐づくユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * お気に入りと書籍のリレーションを定義
     *
     * @return BelongsTo お気に入りに紐づく書籍とのリレーション
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
