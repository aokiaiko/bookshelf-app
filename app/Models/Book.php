<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $casts = [
        'published_date' => 'date',
    ];

    protected $fillable = [
        'user_id', 'title', 'author', 'isbn', 'published_date', 'description', 'image_url',
    ];

    /**
     * 書籍と登録ユーザーのリレーションを定義
     *
     * @return BelongsTo 書籍と登録ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 書籍とレビューのリレーションを定義
     *
     * @return HasMany 書籍に紐づくレビューとのリレーション
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * 書籍とお気に入り登録したユーザーのリレーションを定義
     *
     * @return BelongsToMany お気に入り登録したユーザーとのリレーション
     */
    public function favoriteUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * 書籍とジャンルのリレーションを定義
     *
     * @return BelongsToMany 書籍に紐づくジャンルとのリレーション
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genres');
    }

    /**
     * 書籍と読書計画のリレーションを定義
     *
     * @return HasMany 書籍と読書計画とのリレーション
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }
}
