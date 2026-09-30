<?php

namespace App\Enums;

enum PickupCodeStatus: string
{
    case Active = 'ACTIVE';
    case Expired = 'EXPIRED';
    case Used = 'USED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Expired => 'Kedaluwarsa',
            self::Used => 'Sudah Digunakan',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
            self::Expired => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            self::Used => 'bg-slate-200 text-slate-600 ring-slate-600/20',
            self::Cancelled => 'bg-rose-100 text-rose-700 ring-rose-600/20',
        };
    }
}
