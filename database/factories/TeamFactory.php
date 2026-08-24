<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->company(),
            'allow_multi_day' => true,
            'can_book_exclusive' => false,
        ];
    }
}
