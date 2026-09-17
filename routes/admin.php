<?php

use App\Http\Controllers\Admin\CategoryExportController;
use App\Http\Controllers\Admin\ContactExportController;
use App\Http\Controllers\Admin\CountryExportController;
use App\Http\Controllers\Admin\CouponExportController;
use App\Http\Controllers\Admin\DistrictExportController;
use App\Http\Controllers\Admin\DivisionExportController;
use App\Http\Controllers\Admin\FileManagerController;
use App\Http\Controllers\Admin\PageExportController;
use App\Http\Controllers\Admin\PostExportController;
use App\Http\Controllers\Admin\ProductAttributeExportController;
use App\Http\Controllers\Admin\ProductBrandExportController;
use App\Http\Controllers\Admin\ProductExportController;
use App\Http\Controllers\Admin\ProductVendorExportController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Admin\ServiceExportController;
use App\Http\Controllers\Admin\ShippingMethodExportController;
use App\Http\Controllers\Admin\SubscriberExportController;
use App\Http\Controllers\Admin\TagExportController;
use App\Http\Controllers\Admin\UpazilaExportController;
use App\Http\Controllers\Admin\UserCardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductLabelController;
use App\Livewire\Admin\About;
use App\Livewire\Admin\Advance\Backup;
use App\Livewire\Admin\Advance\Database;
use App\Livewire\Admin\Advance\PasswordGenerator;
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

// About — company info, always reachable regardless of role or feature flags
Route::get('/about', About::class)->name('about');

// Posts — the "blog" feature. Post Categories and Tags moved out to the
// shared Categories/Tags screens (feature:taxonomy below) since both are now
// used by Products too, not just Blog.
Route::middleware('feature:blog')->group(function () {
    Route::get('/posts', Index::class)->name('posts');
    Route::get('/posts/export', [PostExportController::class, 'export'])->name('posts.export');
    Route::get('/posts/create', Form::class)->name('posts.create');
    Route::get('/posts/{id}/edit', Form::class)->name('posts.edit');
});

// Categories (Product + Post, one shared screen — pick a type when creating)
// and Tags (already shared the same way) — reachable regardless of whether
// Blog or Products is the one currently in use.
Route::middleware('feature:taxonomy')->group(function () {
    Route::get('/categories', App\Livewire\Admin\Categories\Index::class)->name('categories');
    Route::get('/categories/export', [CategoryExportController::class, 'export'])->name('categories.export');
    Route::get('/categories/create', App\Livewire\Admin\Categories\Form::class)->name('categories.create');
    Route::get('/categories/{id}/edit', App\Livewire\Admin\Categories\Form::class)->name('categories.edit');

    Route::get('/tags', App\Livewire\Admin\Tags\Index::class)->name('tags');
    Route::get('/tags/export', [TagExportController::class, 'export'])->name('tags.export');
    Route::get('/tags/create', App\Livewire\Admin\Tags\Form::class)->name('tags.create');
    Route::get('/tags/{id}/edit', App\Livewire\Admin\Tags\Form::class)->name('tags.edit');
});

// Pages
Route::middleware('feature:pages')->group(function () {
    Route::get('/pages', App\Livewire\Admin\Pages\Index::class)->name('pages');
    Route::get('/pages/export', [PageExportController::class, 'export'])->name('pages.export');
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

    Route::middleware('feature:env')->group(function () {
        Route::get('/env', App\Livewire\Admin\Env\Index::class)->name('env');
    });

    Route::middleware('feature:email-templates')->group(function () {
        Route::get('/email-templates', App\Livewire\Admin\EmailTemplates\Index::class)->name('email-templates');
    });
});

// Products — Product Categories moved out to the shared Categories screen
// (feature:taxonomy above).
Route::middleware('feature:products')->group(function () {
    Route::get('/products', App\Livewire\Admin\Products\Index::class)->name('products');
    Route::get('/products/export', [ProductExportController::class, 'export'])->name('products.export');
    Route::get('/products/create', App\Livewire\Admin\Products\Form::class)->name('products.create');
    Route::get('/products/{id}', App\Livewire\Admin\Products\Show::class)->name('products.show');
    Route::get('/products/{id}/edit', App\Livewire\Admin\Products\Form::class)->name('products.edit');
    Route::get('/products/{id}/label', [ProductLabelController::class, 'download'])->name('products.label');

    Route::get('/product-attributes', App\Livewire\Admin\ProductAttributes\Index::class)->name('product-attributes');
    Route::get('/product-attributes/export', [ProductAttributeExportController::class, 'export'])->name('product-attributes.export');
    Route::get('/product-attributes/create', App\Livewire\Admin\ProductAttributes\Form::class)->name('product-attributes.create');
    Route::get('/product-attributes/{id}/edit', App\Livewire\Admin\ProductAttributes\Form::class)->name('product-attributes.edit');

    Route::get('/product-vendors', App\Livewire\Admin\ProductVendors\Index::class)->name('product-vendors');
    Route::get('/product-vendors/export', [ProductVendorExportController::class, 'export'])->name('product-vendors.export');
    Route::get('/product-vendors/create', App\Livewire\Admin\ProductVendors\Form::class)->name('product-vendors.create');
    Route::get('/product-vendors/{id}/edit', App\Livewire\Admin\ProductVendors\Form::class)->name('product-vendors.edit');
});

// Brands — still "products" feature-gated, just its own top-level sidebar
// item now rather than nested under Products (see AdminMenuSeeder).
Route::middleware('feature:products')->group(function () {
    Route::get('/product-brands', App\Livewire\Admin\ProductBrands\Index::class)->name('product-brands');
    Route::get('/product-brands/export', [ProductBrandExportController::class, 'export'])->name('product-brands.export');
    Route::get('/product-brands/create', App\Livewire\Admin\ProductBrands\Form::class)->name('product-brands.create');
    Route::get('/product-brands/{id}/edit', App\Livewire\Admin\ProductBrands\Form::class)->name('product-brands.edit');
});

// Services — its own feature toggle, separate from Products.
Route::middleware('feature:services')->group(function () {
    Route::get('/services', App\Livewire\Admin\Services\Index::class)->name('services');
    Route::get('/services/export', [ServiceExportController::class, 'export'])->name('services.export');
    Route::get('/services/create', App\Livewire\Admin\Services\Form::class)->name('services.create');
    Route::get('/services/{id}/edit', App\Livewire\Admin\Services\Form::class)->name('services.edit');
});

// System-only screens — Admin/Super Admin only, not Staff (see access-admin-system gate)
Route::middleware('can:access-admin-system')->group(function () {
    // Contacts (read-only)
    Route::middleware('feature:contacts')->group(function () {
        Route::get('/contacts', App\Livewire\Admin\Contacts\Index::class)->name('contacts');
        Route::get('/contacts/export', [ContactExportController::class, 'export'])->name('contacts.export');
    });

    // Comments — moderation only, no create form (comments are customer-authored).
    Route::middleware('feature:comments')->group(function () {
        Route::get('/comments', App\Livewire\Admin\Comments\Index::class)->name('comments');
    });

    // Reviews — moderation only, no create form (reviews are customer-authored).
    Route::middleware('feature:reviews')->group(function () {
        Route::get('/reviews', App\Livewire\Admin\Reviews\Index::class)->name('reviews');
    });

    // Newsletter Subscribers
    Route::middleware('feature:newsletter')->group(function () {
        Route::get('/subscribers', App\Livewire\Admin\Subscribers\Index::class)->name('subscribers');
        Route::get('/subscribers/export', [SubscriberExportController::class, 'export'])->name('subscribers.export');
    });

    // Roles, Permissions & Users — the "access-control" feature
    Route::middleware('feature:access-control')->group(function () {
        Route::get('/roles', App\Livewire\Admin\Roles\Index::class)->name('roles');
        Route::get('/roles/create', App\Livewire\Admin\Roles\Form::class)->name('roles.create');
        Route::get('/roles/{id}/edit', App\Livewire\Admin\Roles\Form::class)->name('roles.edit');
        Route::get('/permissions', App\Livewire\Admin\Permissions\Index::class)->name('permissions');

        Route::get('/users', App\Livewire\Admin\Users\Index::class)->name('users');
        Route::get('/users/create', App\Livewire\Admin\Users\Form::class)->name('users.create');
        Route::get('/users/{id}/edit', App\Livewire\Admin\Users\Form::class)->name('users.edit');
        Route::get('/users/{user}/card', [UserCardController::class, 'show'])->name('users.card');
        Route::get('/users/{user}/card/download', [UserCardController::class, 'download'])->name('users.card.download');
    });

    // Admin Activity History — the "audit-log" feature
    Route::middleware('feature:audit-log')->group(function () {
        Route::get('/history', App\Livewire\Admin\ActivityLogs\Index::class)->name('history');
    });

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

    // Location — Country, State, Division, District (Zilla) & Upazila hierarchy —
    // the "location" feature
    Route::middleware('feature:location')->group(function () {
        Route::get('/countries', App\Livewire\Admin\Countries\Index::class)->name('countries');
        Route::get('/countries/export', [CountryExportController::class, 'export'])->name('countries.export');
        Route::get('/countries/create', App\Livewire\Admin\Countries\Form::class)->name('countries.create');
        Route::get('/countries/{id}/edit', App\Livewire\Admin\Countries\Form::class)->name('countries.edit');

        Route::get('/divisions', App\Livewire\Admin\Divisions\Index::class)->name('divisions');
        Route::get('/divisions/export', [DivisionExportController::class, 'export'])->name('divisions.export');
        Route::get('/divisions/create', App\Livewire\Admin\Divisions\Form::class)->name('divisions.create');
        Route::get('/divisions/{id}/edit', App\Livewire\Admin\Divisions\Form::class)->name('divisions.edit');

        Route::get('/districts', App\Livewire\Admin\Districts\Index::class)->name('districts');
        Route::get('/districts/export', [DistrictExportController::class, 'export'])->name('districts.export');
        Route::get('/districts/create', App\Livewire\Admin\Districts\Form::class)->name('districts.create');
        Route::get('/districts/{id}/edit', App\Livewire\Admin\Districts\Form::class)->name('districts.edit');

        Route::get('/upazilas', App\Livewire\Admin\Upazilas\Index::class)->name('upazilas');
        Route::get('/upazilas/export', [UpazilaExportController::class, 'export'])->name('upazilas.export');
        Route::get('/upazilas/create', App\Livewire\Admin\Upazilas\Form::class)->name('upazilas.create');
        Route::get('/upazilas/{id}/edit', App\Livewire\Admin\Upazilas\Form::class)->name('upazilas.edit');
    });

    // Advance — Sitemap & Robots.txt generation, and future advanced/technical tools
    Route::middleware('feature:advance')->group(function () {
        Route::get('/advance/sitemap', Sitemap::class)->name('advance.sitemap');
        Route::get('/advance/robots', Robots::class)->name('advance.robots');
        Route::get('/advance/database', Database::class)->name('advance.database');
        Route::get('/advance/backup', Backup::class)->name('advance.backup');
        Route::get('/advance/password-generator', PasswordGenerator::class)->name('advance.password-generator');
    });

    // Orders, Reports & Coupons
    Route::middleware('feature:orders')->group(function () {
        Route::get('/orders', App\Livewire\Admin\Orders\Index::class)->name('orders');
        Route::get('/orders/export', [ReportExportController::class, 'export'])->name('orders.export');
        Route::get('/orders/{id}', Show::class)->name('orders.show');
        Route::get('/orders/{order}/invoice', [InvoiceController::class, 'show'])->name('orders.invoice');
        Route::get('/orders/{order}/invoice/download', [InvoiceController::class, 'download'])->name('orders.invoice.download');
        Route::get('/orders/{order}/address', [InvoiceController::class, 'address'])->name('orders.address');

        Route::get('/reports', App\Livewire\Admin\Reports\Index::class)->name('reports');
        Route::get('/reports/export', [ReportExportController::class, 'export'])->name('reports.export');

        Route::get('/coupons', App\Livewire\Admin\Coupons\Index::class)->name('coupons');
        Route::get('/coupons/export', [CouponExportController::class, 'export'])->name('coupons.export');
        Route::get('/coupons/create', App\Livewire\Admin\Coupons\Form::class)->name('coupons.create');
        Route::get('/coupons/{id}/edit', App\Livewire\Admin\Coupons\Form::class)->name('coupons.edit');

        Route::get('/shipping-methods', App\Livewire\Admin\ShippingMethods\Index::class)->name('shipping-methods');
        Route::get('/shipping-methods/export', [ShippingMethodExportController::class, 'export'])->name('shipping-methods.export');
        Route::get('/shipping-methods/create', App\Livewire\Admin\ShippingMethods\Form::class)->name('shipping-methods.create');
        Route::get('/shipping-methods/{id}/edit', App\Livewire\Admin\ShippingMethods\Form::class)->name('shipping-methods.edit');
    });

    // Discounts
    Route::middleware('feature:discounts')->group(function () {
        Route::get('/discounts', App\Livewire\Admin\Discounts\Index::class)->name('discounts');
        Route::get('/discounts/create', App\Livewire\Admin\Discounts\Form::class)->name('discounts.create');
        Route::get('/discounts/{id}/edit', App\Livewire\Admin\Discounts\Form::class)->name('discounts.edit');
    });
});

// File Manager
Route::middleware(['can:view-file-manager', 'feature:file-manager'])->group(function () {
    Route::get('/file-manager', App\Livewire\Admin\FileManager\Index::class)->name('file-manager');
    Route::get('/file-manager/raw', [FileManagerController::class, 'raw'])->name('file-manager.raw');
    Route::get('/file-manager/download-zip', [FileManagerController::class, 'downloadZip'])->name('file-manager.download-zip');
});
