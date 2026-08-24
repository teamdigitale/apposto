<?php

namespace Database\Factories;

use App\Models\Desk;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Desk>
 */
class DeskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'identifier' => 'D-'.fake()->unique()->numerify('###'),
            'plan_id' => Plan::factory(),
            'active' => true,
        ];
    }
}
