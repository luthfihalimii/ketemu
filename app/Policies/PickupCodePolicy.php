<?php

namespace App\Policies;

use App\Models\PickupCode;
use App\Models\User;

class PickupCodePolicy
{
    /**
     * Only the owner (or an admin) may read a pickup code record.
     */
    public function view(User $user, PickupCode $pickupCode): bool
    {
        return $user->id === $pickupCode->user_id || $user->isAdmin();
    }

    /**
     * Guards and admins redeem codes at the security post.
     */
    public function redeem(User $user, PickupCode $pickupCode): bool
    {
        return $user->canVerifyPickup();
    }
}
