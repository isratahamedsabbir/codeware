<?php

use App\Livewire\Delivery\Auth\Login;
use App\Livewire\Delivery\Orders\Index as OrdersIndex;
use App\Livewire\Delivery\Orders\Show as OrdersShow;
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

Route::middleware(['auth', 'can:access-delivery-portal'])->group(function () {
    Route::get('/', OrdersIndex::class)->name('orders');
    Route::get('/orders/{orderId}', OrdersShow::class)->name('orders.show');
});
