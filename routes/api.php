<?php

use App\Http\Controllers\Api\V1\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Api\V1\Admin\LayoutController as AdminLayoutController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\V1\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\LayoutController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

// Public read endpoints — no auth required
Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');

    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

    Route::get('/settings/public', [SettingsController::class, 'public'])->name('settings.public');
    Route::get('/layout', [LayoutController::class, 'show'])->name('layout.show');

    Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');

    // Admin endpoints — require Sanctum token AND admin role
    Route::middleware(['auth:sanctum', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::get('/posts/{id}', [AdminPostController::class, 'show'])->name('posts.show');
        Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{id}', [AdminPostController::class, 'update'])->name('posts.update');

        Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
        Route::get('/pages/{id}', [AdminPageController::class, 'show'])->name('pages.show');
        Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
        Route::put('/pages/{id}', [AdminPageController::class, 'update'])->name('pages.update');

        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');

        Route::put('/layout', [AdminLayoutController::class, 'update'])->name('layout.update');

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
    });

});
