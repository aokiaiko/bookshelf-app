<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 書籍の更新権限を判定
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Book  $book  更新対象の書籍
     * @return bool 更新を許可する場合はtrue、それ以外はfalse
     */
    public function update(User $user, Book $book): bool
    {
        return $book->user_id === $user->id;
    }

    /**
     * 書籍の削除権限を判定
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Book  $book  削除対象の書籍
     * @return bool 削除を許可する場合はtrue、それ以外はfalse
     */
    public function delete(User $user, Book $book): bool
    {
        return $book->user_id === $user->id;
    }
}
