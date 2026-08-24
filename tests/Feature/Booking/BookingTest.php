<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\Notification;

test('two users cannot confirm the same desk for the same slot', function () {
    Notification::fake();

    $world = deskWorld();
    $date = nextWorkday();
    $alice = userOnTeam($world['team'], ['priority' => 1]);
    $bob = userOnTeam($world['team'], ['priority' => 1]);

    $session = [
        'desk_id' => $world['desk']->id,
        'start_date' => $date,
        'end_date' => $date,
        'start_time' => '09:00',
        'end_time' => '18:00',
        'is_exclusive' => false,
    ];

    $this->actingAs($alice)
        ->withSession(['booking' => $session])
        ->post(route('booking.complete'))
        ->assertRedirect(route('booking.my'));

    $this->actingAs($bob)
        ->withSession(['booking' => $session])
        ->post(route('booking.complete'))
        ->assertSessionHas('error');

    expect(Booking::where('desk_id', $world['desk']->id)->where('status', BookingStatus::Confirmed)->count())
        ->toBe(1);
});

test('higher priority user can override an existing booking', function () {
    Notification::fake();

    $world = deskWorld();
    $date = nextWorkday();
    $alice = userOnTeam($world['team'], ['priority' => 1]);
    $bob = userOnTeam($world['team'], ['priority' => 5]);

    Booking::factory()->create([
        'user_id' => $alice->id,
        'desk_id' => $world['desk']->id,
        'plan_id' => $world['plan']->id,
        'start_date' => $date,
        'end_date' => $date,
        'from_date' => $date.' 09:00:00',
        'to_date' => $date.' 18:00:00',
        'status' => BookingStatus::Confirmed->value,
    ]);

    $this->actingAs($bob)
        ->withSession(['booking' => [
            'desk_id' => $world['desk']->id,
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '09:00',
            'end_time' => '18:00',
            'is_exclusive' => false,
        ]])
        ->post(route('booking.complete'))
        ->assertRedirect(route('booking.my'));

    expect(Booking::where('user_id', $alice->id)->first()->status)->toBe(BookingStatus::Overridden->value)
        ->and(Booking::where('user_id', $bob->id)->where('status', BookingStatus::Confirmed)->count())->toBe(1);
});

test('a user cannot mass-cancel someone else bookings', function () {
    $world = deskWorld();
    $date = nextWorkday();
    $alice = userOnTeam($world['team']);
    $bob = userOnTeam($world['team']);

    $booking = Booking::factory()->create([
        'user_id' => $alice->id,
        'desk_id' => $world['desk']->id,
        'plan_id' => $world['plan']->id,
        'start_date' => $date,
        'end_date' => $date,
        'from_date' => $date.' 09:00:00',
        'to_date' => $date.' 18:00:00',
        'status' => BookingStatus::Confirmed->value,
    ]);

    $this->actingAs($bob)
        ->post(route('bookings.multiCancel'), ['booking_ids' => [$booking->id]])
        ->assertSessionHas('error');

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed->value);
});

test('a user can cancel their own booking', function () {
    $world = deskWorld();
    $date = nextWorkday();
    $alice = userOnTeam($world['team']);

    $booking = Booking::factory()->create([
        'user_id' => $alice->id,
        'desk_id' => $world['desk']->id,
        'plan_id' => $world['plan']->id,
        'start_date' => $date,
        'end_date' => $date,
        'from_date' => $date.' 09:00:00',
        'to_date' => $date.' 18:00:00',
        'status' => BookingStatus::Confirmed->value,
    ]);

    $this->actingAs($alice)
        ->post(route('bookings.cancel', $booking))
        ->assertRedirect(route('bookings.current'));

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled->value);
});
