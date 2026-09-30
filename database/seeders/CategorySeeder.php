<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, icon: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Dompet', 'icon' => 'wallet'],
        ['name' => 'Kartu Identitas', 'icon' => 'identification'],
        ['name' => 'Ponsel & Aksesori', 'icon' => 'device-phone-mobile'],
        ['name' => 'Elektronik', 'icon' => 'computer-desktop'],
        ['name' => 'Kunci', 'icon' => 'key'],
        ['name' => 'Tas & Ransel', 'icon' => 'briefcase'],
        ['name' => 'Pakaian & Jaket', 'icon' => 'user'],
        ['name' => 'Helm', 'icon' => 'shield-check'],
        ['name' => 'Buku & Alat Tulis', 'icon' => 'book-open'],
        ['name' => 'Lainnya', 'icon' => 'tag'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }
    }
}
