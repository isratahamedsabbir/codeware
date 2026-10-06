<?php

it('sets hardening headers and a report-only CSP on web responses', function () {
    $response = $this->get('/up');
    $response = $this->get('/login');

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Content-Security-Policy-Report-Only'))
        ->toContain("frame-ancestors 'self'")->toContain("object-src 'none'")->toContain("'nonce-");
});

it('enforces the CSP when report-only is off', function () {
    config(['security.csp.report_only' => false]);

    $response = $this->get('/login');

    expect($response->headers->has('Content-Security-Policy'))->toBeTrue()
        ->and($response->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();
});

it('adds the headers to API responses', function () {
    $this->getJson('/api/v1/posts')->assertHeader('X-Content-Type-Options', 'nosniff');
});
