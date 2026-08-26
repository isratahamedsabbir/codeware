<?php

test('home renders the active theme\'s public homepage', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
