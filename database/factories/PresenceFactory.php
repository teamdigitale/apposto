<?php

namespace Database\Factories;

use App\Models\Presence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presence>
 */
class PresenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'status' => 'presente',
        ];
    }
}
