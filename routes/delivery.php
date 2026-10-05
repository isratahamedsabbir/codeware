<?php

use App\Livewire\Delivery\Auth\Login;
use App\Livewire\Delivery\Dashboard;
use App\Livewire\Delivery\Orders\Index as OrdersIndex;
use App\Livewire\Delivery\Orders\Show as OrdersShow;
use App\Livewire\Delivery\Profile;
use App\Livewire\Security\MfaRequired;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Its own login — not Fortify's shared one — so the delivery portal is a
// fully separate panel, same as the vendor portal (routes/vendor.php).
Route::get('/login', Login::class)->name('login');

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('delivery.login');
})->middleware('auth')->name('logout');

// Forced MFA enrolment for this portal — see the identical route in
// routes/admin.php. Behind 'auth' + the delivery gate but not behind 'mfa',
// which is what makes the policy satisfiable at all.
Route::get('/mfa-required', MfaRequired::class)
    ->middleware(['auth', 'can:access-delivery-portal'])
    ->name('mfa.required');

// 'mfa' last for the same reason as in the admin panel: it only ever sees a
// request that already passed 'auth' and the delivery gate, which is how it
// knows this account belongs to the delivery audience.
Route::middleware(['auth', 'can:access-delivery-portal', 'mfa'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/profile', Profile::class)->name('profile');

    Route::get('/orders', OrdersIndex::class)->name('orders');
    Route::get('/orders/{orderId}', OrdersShow::class)->name('orders.show');
});
