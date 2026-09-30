<?php

namespace App\Enums;

use App\Exceptions\InvalidStatusTransitionException;

enum ItemStatus: string
{
    case Reported = 'REPORTED';
    case WaitingDeposit = 'WAITING_DEPOSIT';
    case Stored = 'STORED';
    case Claimed = 'CLAIMED';
    case Verified = 'VERIFIED';
    case ReadyForPickup = 'READY_FOR_PICKUP';
    case Returned = 'RETURNED';
    case Expired = 'EXPIRED';
    case Rejected = 'REJECTED';

    /**
     * Human readable label shown in the UI (Indonesian).
     */
    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Dilaporkan',
            self::WaitingDeposit => 'Menunggu Dititipkan',
            self::Stored => 'Tersedia',
            self::Claimed => 'Sedang Diklaim',
            self::Verified => 'Terverifikasi',
            self::ReadyForPickup => 'Siap Diambil',
            self::Returned => 'Sudah Dikembalikan',
            self::Expired => 'Kedaluwarsa',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Tailwind classes for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Reported, self::WaitingDeposit => 'bg-sky-100 text-sky-700 ring-sky-600/20',
            self::Stored => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
            self::Claimed, self::Verified => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            self::ReadyForPickup => 'bg-indigo-100 text-indigo-700 ring-indigo-600/20',
            self::Returned => 'bg-slate-200 text-slate-700 ring-slate-600/20',
            self::Expired, self::Rejected => 'bg-rose-100 text-rose-700 ring-rose-600/20',
        };
    }

    /**
     * Icon name rendered next to the label.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Reported => 'megaphone',
            self::WaitingDeposit => 'clock',
            self::Stored => 'archive-box',
            self::Claimed => 'hand-raised',
            self::Verified => 'check-badge',
            self::ReadyForPickup => 'ticket',
            self::Returned => 'check-circle',
            self::Expired => 'clock',
            self::Rejected => 'x-circle',
        };
    }

    /**
     * Whether the item is still visible to the public.
     */
    public function isPubliclyAvailable(): bool
    {
        return in_array($this, [self::Stored, self::Claimed, self::Verified, self::ReadyForPickup], true);
    }

    /**
     * Whether a student may still open a claim for the item.
     */
    public function acceptsClaims(): bool
    {
        return $this === self::Stored;
    }

    /**
     * Statuses the item is allowed to move to, based on the transition rules.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            // REPORTED is a lost report. It is never handed to security staff,
            // so it must not reach STORED/WAITING_DEPOSIT; it is either resolved
            // (the owner got their item back) or taken down.
            self::Reported => [self::Returned, self::Rejected],
            self::WaitingDeposit => [self::Stored, self::Rejected],
            self::Stored => [self::Claimed, self::Expired, self::Rejected],
            self::Claimed => [self::Verified, self::Stored],
            // VERIFIED may be handed over directly when a code is redeemed.
            self::Verified => [self::ReadyForPickup, self::Returned, self::Stored],
            // An item awaiting pickup must not be expired out from under a live
            // code; it is returned, put back on the shelf, or taken down.
            self::ReadyForPickup => [self::Returned, self::Stored, self::Rejected],
            self::Expired => [self::Stored],
            self::Returned => [],
            // Rejected is reversible for a taken-down found report, but a
            // rejected lost report returns to the lost-report state.
            self::Rejected => [self::Stored, self::Reported],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Guard a status change so the item never moves backwards silently.
     *
     * @throws InvalidStatusTransitionException
     */
    public function transitionTo(self $target): self
    {
        if ($this === $target) {
            return $this;
        }

        if (! $this->canTransitionTo($target)) {
            throw InvalidStatusTransitionException::make($this, $target);
        }

        return $target;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            function (array $carry, self $status): array {
                $carry[$status->value] = $status->label();

                return $carry;
            },
            [],
        );
    }
}
