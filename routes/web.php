<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
});


Route::get('/token', function () {
    $token = auth()->user()->createToken('test-token', ['*'], now()->addMinutes(config('app.puck_session', 5)))->plainTextToken;
    return response()->json(['token' => $token]);
})->middleware(['auth']);

require __DIR__.'/settings.php';
