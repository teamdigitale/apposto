<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $date = now()->next('Wednesday')->toDateString();

        return [
            'user_id' => User::factory(),
            'desk_id' => Desk::factory(),
            'plan_id' => fn (array $attrs) => Desk::find($attrs['desk_id'])?->plan_id,
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '09:00',
            'end_time' => '18:00',
            'from_date' => $date.' 09:00:00',
            'to_date' => $date.' 18:00:00',
            'status' => BookingStatus::Confirmed->value,
            'is_exclusive' => false,
        ];
    }
}
