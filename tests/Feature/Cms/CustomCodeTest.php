<?php

use App\Models\Setting;

beforeEach(function () {
    Setting::set('custom_head_code', '<meta name="custom-head-probe" content="1">');
    Setting::set('custom_body_code', '<script>window.customBodyProbe = 1;</script>');
});

it('injects the custom head and body code on every theme\'s public pages', function (string $theme) {
    Setting::set('site_theme', $theme);

    $html = $this->get('/')->assertOk()->getContent();

    // Head code lands inside <head>, body code just before </body>.
    expect(strpos($html, 'custom-head-probe'))->toBeLessThan(strpos($html, '</head>'))
        ->and($html)->toContain('<script>window.customBodyProbe = 1;</script>')
        ->and(strpos($html, 'customBodyProbe'))->toBeLessThan(strrpos($html, '</body>'))
        ->and(strpos($html, 'customBodyProbe'))->toBeGreaterThan(strpos($html, '</head>'));
})->with(['ecommerce', 'portfolio', 'default']);

it('injects the custom code on inner ecommerce pages too', function () {
    Setting::set('site_theme', 'ecommerce');

    $this->get('/shop')->assertOk()
        ->assertSee('custom-head-probe', false)
        ->assertSee('customBodyProbe', false);
});

it('keeps the custom code off the admin and auth screens', function () {
    Setting::set('site_theme', 'default');

    $this->get(route('login'))->assertOk()
        ->assertDontSee('custom-head-probe', false)
        ->assertDontSee('customBodyProbe', false);
});

it('outputs nothing when no custom code is set', function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('custom_head_code', '');
    Setting::set('custom_body_code', '');

    $this->get('/')->assertOk()
        ->assertDontSee('custom-head-probe', false)
        ->assertDontSee('customBodyProbe', false);
});
