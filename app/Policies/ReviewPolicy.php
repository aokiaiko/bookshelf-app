<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * レビューの更新権限を判定
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Review  $review  更新対象のrレビュー
     * @return bool 更新を許可する場合はtrue、それ以外はfalse
     */
    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    /**
     * レビューの削除権限を判定
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Review  $review  削除対象の書籍
     * @return bool 削除を許可する場合はtrue、それ以外はfalse
     */
    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
