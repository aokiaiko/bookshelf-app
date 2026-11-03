<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'book_id', 'rating', 'comment',
    ];

    /**
     * レビューとユーザーのリレーションを定義
     *
     * @return BelongsTo レビューーに紐づくユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * レビューと書籍のリレーションを定義
     *
     * @return BelongsTo レビューに紐づく書籍とのリレーション
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * レビューといいねしたユーザーのリレーションを定義
     *
     * @return BelongsToMany レビューにいいねしたユーザーとのリレーション
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_likes');
    }
}
