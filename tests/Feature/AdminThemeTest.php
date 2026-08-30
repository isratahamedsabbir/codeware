<?php

use App\Models\Setting;
use App\Support\Themes;

it('falls back to the default theme when the selected theme folder no longer exists', function () {
    Setting::set('site_theme', 'admin');

    expect(Themes::active())->toBe('default');

    $this->get('/')->assertOk();
});
