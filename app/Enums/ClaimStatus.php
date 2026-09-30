<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Submitted = 'SUBMITTED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';
    case Completed = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Menunggu Verifikasi',
            self::Approved => 'Disetujui',
            self::Rejected => 'Gagal Verifikasi',
            self::Cancelled => 'Dibatalkan',
            self::Completed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            self::Approved => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
            self::Rejected => 'bg-rose-100 text-rose-700 ring-rose-600/20',
            self::Cancelled => 'bg-slate-200 text-slate-600 ring-slate-600/20',
            self::Completed => 'bg-indigo-100 text-indigo-700 ring-indigo-600/20',
        };
    }

    /** Whether the claim currently blocks other students from claiming the item. */
    public function isActive(): bool
    {
        return in_array($this, [self::Submitted, self::Approved], true);
    }
}
