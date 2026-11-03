<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'review_id',
    ];

    /**
     * いいねとユーザーのリレーションを定義
     *
     * @return BelongsTo いいねに紐づくユーザとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * いいねとレビューのリレーションを定義
     *
     * @return BelongsTo いいねに紐づくレビューとのリレーション
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
