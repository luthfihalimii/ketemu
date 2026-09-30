<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A claim is private between the claimant, the item's reporter, and admins.
     */
    public function view(User $user, Claim $claim): bool
    {
        return $user->id === $claim->user_id
            || $user->id === $claim->item?->user_id
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, Claim $claim): bool
    {
        return $user->id === $claim->user_id && $claim->status->isActive();
    }
}
