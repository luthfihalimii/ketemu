<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Guard = 'guard';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Mahasiswa',
            self::Guard => 'Satpam',
            self::Admin => 'Admin',
        };
    }

    /**
     * Roles allowed to perform administrative moderation.
     *
     * @return array<int, self>
     */
    public static function moderators(): array
    {
        return [self::Admin];
    }
}
