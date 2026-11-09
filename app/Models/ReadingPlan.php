<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'status' => ReadingPlanStatus::class,
    ];

    protected $fillable = [
        'user_id', 'book_id', 'target_date', 'completed_at', 'status',
    ];

    /**
     * 読書計画と所有者のリレーションを定義
     *
     * @return BelongsTo 読書計画と所有者とのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 読書計画と書籍のリレーションを定義
     *
     * @return BelongsTo 読書計画と書籍とのリレーション
     */
    public function book(): BelongsTo
    {
        return $this->BelongsTo(Book::class);
    }
}
