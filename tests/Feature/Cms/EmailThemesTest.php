<?php

use App\Support\EmailThemes;

it('lists every theme blade file in the email templates folder', function () {
    $themes = EmailThemes::all();

    expect($themes)
        ->toHaveKey('default')
        ->toHaveKey('ocean')
        ->toHaveKey('slate')
        ->toHaveKey('sunset')
        ->toHaveKey('royal')
        ->and($themes['default'])->toBe('Default');
});

it('resolves the dotted view for an installed theme', function () {
    expect(EmailThemes::view('ocean'))->toBe('emails.templates.ocean');
});

it('falls back to default when the theme slug is unknown, empty, or null', function () {
    expect(EmailThemes::view('not-a-theme'))->toBe('emails.templates.default')
        ->and(EmailThemes::view(''))->toBe('emails.templates.default')
        ->and(EmailThemes::view(null))->toBe('emails.templates.default');
});
