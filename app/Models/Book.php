<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'author', 'isbn', 'published_at', 'description', 'image_url',
    ];

    protected $casts = [
        'published_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favoriteUsers()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'book_genres');
    }
}
