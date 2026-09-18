<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Create a new policy instance.
     */
    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
