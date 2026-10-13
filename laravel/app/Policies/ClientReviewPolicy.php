<?php
namespace App\Policies;

use App\Models\ClientReview;
use App\Models\User;

class ClientReviewPolicy
{
    public function view(User $user, ClientReview $review): bool
    {
        return $user->hasGymPermission('feedback.manage')
            || ($user->role === 'client' && $review->user_id === $user->id);
    }

    public function moderate(User $user, ClientReview $review): bool
    {
        return $user->hasGymPermission('feedback.manage');
    }
}
