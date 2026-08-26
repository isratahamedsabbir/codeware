<?php

use App\Http\Controllers\Api\V1\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Api\V1\Admin\LayoutController as AdminLayoutController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\V1\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\CmsController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\LayoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

// Customer account auth — API-only (no admin panel UI), backed by the same `users`
// table as the admin/Fortify web login (is_admin stays false for these accounts).
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register');
    Route::post('/login', [LoginController::class, 'store'])->name('login')->middleware('throttle:login');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.update');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('email.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')->name('email.resend');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Public read endpoints — no auth required
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/settings/public', [SettingsController::class, 'public'])->name('settings.public');
Route::get('/layout', [LayoutController::class, 'show'])->name('layout.show');

Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cms', [CmsController::class, 'index'])->name('cms.index');

Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::post('/request-demo', [ContactController::class, 'requestDemo'])->name('request-demo.store');
Route::post('/book-demo', [ContactController::class, 'bookDemo'])->name('book-demo.store');

Route::middleware('feature:orders')->group(function () {
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
});

// Admin endpoints — require Sanctum token AND admin role
Route::middleware(['auth:sanctum', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{id}', [AdminPostController::class, 'show'])->name('posts.show');
    Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
    Route::put('/posts/{id}', [AdminPostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{id}', [AdminPageController::class, 'show'])->name('pages.show');
    Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
    Route::put('/pages/{id}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{id}', [AdminPageController::class, 'destroy'])->name('pages.destroy');

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/product-categories', [AdminProductCategoryController::class, 'index'])->name('product-categories.index');
    Route::post('/product-categories', [AdminProductCategoryController::class, 'store'])->name('product-categories.store');
    Route::put('/product-categories/{id}', [AdminProductCategoryController::class, 'update'])->name('product-categories.update');
    Route::delete('/product-categories/{id}', [AdminProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::put('/products/{id}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/contacts', [AdminContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{id}', [AdminContactController::class, 'show'])->name('contacts.show');
    Route::put('/contacts/{id}', [AdminContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{id}', [AdminContactController::class, 'destroy'])->name('contacts.destroy');

    // Stricter than the rest of this group: mirrors `access-admin-system` on the
    // Livewire side (routes/admin.php) — Users and Settings (including Layout,
    // which lives on the Settings screen's own Layout tab) are Admin/Super Admin
    // only there. Staff passes the outer `access-admin` gate but not this one.
    Route::middleware('can:access-admin-system')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
        Route::put('/users/{id}/password', [AdminUserController::class, 'updatePassword'])->name('users.password.update');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

        Route::put('/layout', [AdminLayoutController::class, 'update'])->name('layout.update');
    });
});
