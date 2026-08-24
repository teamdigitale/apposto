<?php

use App\Enums\BookingStatus;

test('booking status labels cover every case', function () {
    expect(BookingStatus::labels())->toMatchArray([
        0 => 'confermata',
        1 => 'cancellata',
        2 => 'rubata',
        3 => 'conclusa',
    ]);
});
