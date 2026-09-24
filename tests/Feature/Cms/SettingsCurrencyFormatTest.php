<?php

use App\Models\Setting;

it('formats money with the configured decimal places', function () {
    Setting::set('currency_symbol', '৳');

    Setting::set('decimal_places', '2');
    expect(format_money(39.99))->toBe('৳ 39.99');

    Setting::set('decimal_places', '1');
    expect(format_money(39.9))->toBe('৳ 39.9');

    Setting::set('decimal_places', '0');
    expect(format_money(39.99))->toBe('৳ 40');

    Setting::set('decimal_places', '0');
    expect(format_money(1250))->toBe('৳ 1,250');
});

it('keeps whole amounts clean regardless of decimal places', function () {
    Setting::set('currency_symbol', '৳');

    Setting::set('decimal_places', '2');
    expect(format_money(260))->toBe('৳ 260');
});

it('allows overriding decimal places per call', function () {
    Setting::set('currency_symbol', '৳');
    Setting::set('decimal_places', '0');

    expect(format_money(39.99, 2))->toBe('৳ 39.99');
});
