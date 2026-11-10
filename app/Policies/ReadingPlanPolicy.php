<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 読書計画の更新権限を判定
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  更新対象の読書計画
     * @return bool 更新を許可する場合はtrue、それ以外はfalse
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id;
    }

    /**
     * 書籍の削除権限を判定
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  削除対象の読書計画
     * @return bool 削除を許可する場合はtrue、それ以外はfalse
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id;
    }
}
