<?php

namespace Database\Factories;

use App\Models\Workplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workplace>
 */
class WorkplaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->city().' Hub',
            'address' => fake()->address(),
            'num_place' => 1,
        ];
    }
}
