<?php

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => Str::title(fake()->words(2, true)),
            'description' => fake()->sentence(),
            'private_note' => null,
            'location_id' => Location::factory(),
            'location_detail' => null,
            'occurred_at' => now()->subHours(fake()->numberBetween(1, 72)),
            'color' => fake()->randomElement(['Hitam', 'Biru', 'Merah', 'Putih', 'Abu-abu']),
            'brand' => fake()->optional()->company(),
            'deposit_location_id' => Location::factory()->securityPost(),
            'status' => ItemStatus::Stored,
            'verification_question' => 'Apa ciri khusus barang ini?',
            'verification_answer' => Item::normalizeAnswer('stiker biru di dalam'),
            'stored_at' => now(),
        ];
    }

    public function status(ItemStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ItemStatus::Reported,
            'deposit_location_id' => null,
            'stored_at' => null,
            // Laporan hilang tidak punya jawaban verifikasi; jawaban ditulis
            // oleh penemu, bukan pemilik.
            'verification_answer' => null,
        ]);
    }
}
