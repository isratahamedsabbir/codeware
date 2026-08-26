<?php

use App\Models\Setting;
use Carbon\CarbonImmutable;

it('display_timezone falls back to config when no timezone setting is stored', function () {
    expect(display_timezone())->toBe(config('app.display_timezone', 'UTC'));
});

it('display_timezone returns the admin-configured timezone setting', function () {
    Setting::set('timezone', 'Asia/Dhaka');

    expect(display_timezone())->toBe('Asia/Dhaka');
});

it('the toDisplay carbon macro renders a UTC instant in the admin timezone', function () {
    Setting::set('timezone', 'Asia/Dhaka');

    $utc = CarbonImmutable::parse('2026-01-14 20:00:00', 'UTC');

    expect($utc->toDisplay('Y-m-d H:i'))->toBe('2026-01-15 02:00');

    Setting::set('timezone', 'UTC');
});
