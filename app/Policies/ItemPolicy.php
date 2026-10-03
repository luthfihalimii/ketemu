<?php

namespace App\Policies;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Anyone (including guests) may browse the public catalogue.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Public item detail is visible for discoverable items or to the reporter.
     */
    public function view(?User $user, Item $item): bool
    {
        if ($item->isPubliclyAvailable()) {
            return true;
        }

        return $user !== null && ($user->id === $item->user_id || $user->isAdmin());
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Item $item): bool
    {
        return $user->id === $item->user_id || $user->isAdmin();
    }

    /**
     * Only the finder (or an admin) may confirm that an item was handed over,
     * and only for a found report. Confirming a lost report would publish it
     * into the public catalogue as an item nobody is holding.
     */
    public function confirmDeposit(User $user, Item $item): bool
    {
        if (! $item->isFoundReport() || $item->status !== ItemStatus::WaitingDeposit) {
            return false;
        }

        return $user->id === $item->user_id || $user->isAdmin();
    }

    /**
     * Nobody in the MVP may silently delete a report; statuses cover removal.
     */
    public function delete(User $user, Item $item): bool
    {
        return false;
    }

    public function moderate(User $user, Item $item): bool
    {
        return $user->isAdmin();
    }

    /**
     * The reporter can never claim their own item.
     */
    public function claim(User $user, Item $item): bool
    {
        return $user->id !== $item->user_id && $item->status->acceptsClaims();
    }
}
