<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * ジャンルと書籍のリレーションを定義
     *
     * @return BelongsToMany ジャンルに紐づく書籍のリレーション
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_genres');
    }
}
