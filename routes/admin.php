<?php

use App\Http\Controllers\Admin\FileManagerController;
use App\Http\Controllers\Admin\ProductExportController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Admin\UserCardController;
use App\Http\Controllers\InvoiceController;
use App\Livewire\Admin\Advance\Robots;
use App\Livewire\Admin\Advance\Sitemap;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Orders\Show;
use App\Livewire\Admin\Posts\Form;
use App\Livewire\Admin\Posts\Index;
use App\Livewire\Admin\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');

// Profile
Route::get('/profile', Profile::class)->name('profile');

// Posts, Post Categories, Tags — the "blog" feature
Route::middleware('feature:blog')->group(function () {
    Route::get('/posts', Index::class)->name('posts');
    Route::get('/posts/create', Form::class)->name('posts.create');
    Route::get('/posts/{id}/edit', Form::class)->name('posts.edit');

    Route::get('/post-categories', App\Livewire\Admin\PostCategories\Index::class)->name('post-categories');
    Route::get('/post-categories/create', App\Livewire\Admin\PostCategories\Form::class)->name('post-categories.create');
    Route::get('/post-categories/{id}/edit', App\Livewire\Admin\PostCategories\Form::class)->name('post-categories.edit');

    Route::get('/tags', App\Livewire\Admin\Tags\Index::class)->name('tags');
    Route::get('/tags/create', App\Livewire\Admin\Tags\Form::class)->name('tags.create');
    Route::get('/tags/{id}/edit', App\Livewire\Admin\Tags\Form::class)->name('tags.edit');
});

// Pages
Route::middleware('feature:pages')->group(function () {
    Route::get('/pages', App\Livewire\Admin\Pages\Index::class)->name('pages');
    Route::get('/pages/create', App\Livewire\Admin\Pages\Form::class)->name('pages.create');
    Route::get('/pages/{id}/edit', App\Livewire\Admin\Pages\Form::class)->name('pages.edit');
});

// CMS — freeform titles/descriptions/buttons/cards/images sections, always scoped
// to one Page (reached from that page's row on the Pages screen, never standalone)
Route::middleware('feature:cms')->group(function () {
    Route::get('/pages/{pageId}/cms', App\Livewire\Admin\Cms\Index::class)->name('cms');
    Route::get('/pages/{pageId}/cms/create', App\Livewire\Admin\Cms\Form::class)->name('cms.create');
    Route::get('/pages/{pageId}/cms/{id}/edit', App\Livewire\Admin\Cms\Form::class)->name('cms.edit');
});

// Media Library (content — Staff included)
Route::middleware('feature:media-library')->group(function () {
    Route::get('/media-library', App\Livewire\Admin\MediaLibrary\Index::class)->name('media-library');
});

// Chat — every registered user can 1-on-1 chat with any other registered user
Route::middleware('feature:chat')->group(function () {
    Route::get('/chat/{recipient?}', App\Livewire\Admin\Chat\Index::class)->name('chat');
});

// Settings & Email Templates — Admin/Super Admin only, not Staff
Route::middleware('can:access-admin-system')->group(function () {
    Route::get('/settings', App\Livewire\Admin\Settings\Index::class)->name('settings');
    Route::get('/seo', App\Livewire\Admin\Seo\Index::class)->name('seo');
    Route::get('/social', App\Livewire\Admin\Social\Index::class)->name('social');
    Route::get('/payment-gateways', App\Livewire\Admin\PaymentGateways\Index::class)->name('payment-gateways');
    Route::get('/features', App\Livewire\Admin\Features\Index::class)->name('features');

    Route::middleware('feature:email-templates')->group(function () {
        Route::get('/email-templates', App\Livewire\Admin\EmailTemplates\Index::class)->name('email-templates');
    });
});

// Products, Product Categories
Route::middleware('feature:products')->group(function () {
    Route::get('/products', App\Livewire\Admin\Products\Index::class)->name('products');
    Route::get('/products/export', [ProductExportController::class, 'export'])->name('products.export');
    Route::get('/products/create', App\Livewire\Admin\Products\Form::class)->name('products.create');
    Route::get('/products/{id}/edit', App\Livewire\Admin\Products\Form::class)->name('products.edit');

    Route::get('/product-categories', App\Livewire\Admin\ProductCategories\Index::class)->name('product-categories');
    Route::get('/product-categories/create', App\Livewire\Admin\ProductCategories\Form::class)->name('product-categories.create');
    Route::get('/product-categories/{id}/edit', App\Livewire\Admin\ProductCategories\Form::class)->name('product-categories.edit');
});

// System-only screens — Admin/Super Admin only, not Staff (see access-admin-system gate)
Route::middleware('can:access-admin-system')->group(function () {
    // Contacts (read-only)
    Route::middleware('feature:contacts')->group(function () {
        Route::get('/contacts', App\Livewire\Admin\Contacts\Index::class)->name('contacts');
    });

    // Roles & Permissions
    Route::get('/roles', App\Livewire\Admin\Roles\Index::class)->name('roles');
    Route::get('/roles/create', App\Livewire\Admin\Roles\Form::class)->name('roles.create');
    Route::get('/roles/{id}/edit', App\Livewire\Admin\Roles\Form::class)->name('roles.edit');
    Route::get('/permissions', App\Livewire\Admin\Permissions\Index::class)->name('permissions');

    // Users
    Route::get('/users', App\Livewire\Admin\Users\Index::class)->name('users');
    Route::get('/users/create', App\Livewire\Admin\Users\Form::class)->name('users.create');
    Route::get('/users/{id}/edit', App\Livewire\Admin\Users\Form::class)->name('users.edit');
    Route::get('/users/{user}/card', [UserCardController::class, 'show'])->name('users.card');
    Route::get('/users/{user}/card/download', [UserCardController::class, 'download'])->name('users.card.download');

    // Admin Activity History
    Route::get('/history', App\Livewire\Admin\ActivityLogs\Index::class)->name('history');

    // Localization
    Route::middleware('feature:localization')->group(function () {
        Route::get('/languages', App\Livewire\Admin\Languages\Index::class)->name('languages');
        Route::get('/languages/create', App\Livewire\Admin\Languages\Form::class)->name('languages.create');
        Route::get('/languages/{id}/edit', App\Livewire\Admin\Languages\Form::class)->name('languages.edit');
        Route::get('/translations', App\Livewire\Admin\Translations\Index::class)->name('translations');
    });

    // Menu
    Route::middleware('feature:menu')->group(function () {
        Route::get('/menu', App\Livewire\Admin\Menu\Index::class)->name('menu');
    });

    // Advance — Sitemap & Robots.txt generation, and future advanced/technical tools
    Route::middleware('feature:advance')->group(function () {
        Route::get('/advance/sitemap', Sitemap::class)->name('advance.sitemap');
        Route::get('/advance/robots', Robots::class)->name('advance.robots');
    });

    // Orders, Reports & Coupons
    Route::middleware('feature:orders')->group(function () {
        Route::get('/orders', App\Livewire\Admin\Orders\Index::class)->name('orders');
        Route::get('/orders/export', [ReportExportController::class, 'export'])->name('orders.export');
        Route::get('/orders/{id}', Show::class)->name('orders.show');
        Route::get('/orders/{order}/invoice', [InvoiceController::class, 'show'])->name('orders.invoice');
        Route::get('/orders/{order}/invoice/download', [InvoiceController::class, 'download'])->name('orders.invoice.download');

        Route::get('/reports', App\Livewire\Admin\Reports\Index::class)->name('reports');
        Route::get('/reports/export', [ReportExportController::class, 'export'])->name('reports.export');

        Route::get('/coupons', App\Livewire\Admin\Coupons\Index::class)->name('coupons');
        Route::get('/coupons/create', App\Livewire\Admin\Coupons\Form::class)->name('coupons.create');
        Route::get('/coupons/{id}/edit', App\Livewire\Admin\Coupons\Form::class)->name('coupons.edit');
    });
});

// File Manager
Route::middleware(['can:view-file-manager', 'feature:file-manager'])->group(function () {
    Route::get('/file-manager', App\Livewire\Admin\FileManager\Index::class)->name('file-manager');
    Route::get('/file-manager/raw', [FileManagerController::class, 'raw'])->name('file-manager.raw');
    Route::get('/file-manager/download-zip', [FileManagerController::class, 'downloadZip'])->name('file-manager.download-zip');
});
