<?php

use App\Models\User;

it('admin user can create sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    expect($token)->toBeString()->not->toBeEmpty();
});

it('cms config has editor_base_url', function () {
    expect(config('cms.editor_base_url'))->toBeString();
});
