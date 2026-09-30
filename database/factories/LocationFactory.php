<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Gedung '.fake()->unique()->bothify('?##'),
            'type' => 'building',
            'detail' => null,
            'is_active' => true,
        ];
    }

    public function securityPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pos Satpam '.fake()->unique()->bothify('?##'),
            'type' => 'security_post',
        ]);
    }
}
