<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Workplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => 'Piano '.fake()->randomDigitNotNull(),
            'workplace_id' => Workplace::factory(),
        ];
    }
}
