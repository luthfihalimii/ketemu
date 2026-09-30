<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Buildings students can pick when reporting where an item was found.
     *
     * @var array<int, string>
     */
    private const BUILDINGS = [
        'Gedung D1',
        'Gedung D2',
        'Gedung D3',
        'Gedung D4',
        'Gedung D5',
        'Gedung A',
        'Gedung B',
        'Gedung C',
        'Perpustakaan',
        'Kantin',
        'Area Parkir',
        'Lapangan',
        'Masjid',
        'Gerbang Utama',
    ];

    /**
     * Security posts where found items are physically deposited.
     *
     * @var array<int, string>
     */
    private const SECURITY_POSTS = [
        'Pos Satpam Gedung D4',
        'Pos Satpam Gedung D1',
        'Pos Satpam Gedung A',
        'Pos Satpam Gerbang Utama',
        'Pos Satpam Area Parkir',
    ];

    public function run(): void
    {
        foreach (self::BUILDINGS as $name) {
            Location::query()->updateOrCreate(
                ['name' => $name],
                ['type' => 'building', 'is_active' => true],
            );
        }

        foreach (self::SECURITY_POSTS as $name) {
            Location::query()->updateOrCreate(
                ['name' => $name],
                ['type' => 'security_post', 'is_active' => true],
            );
        }
    }
}
