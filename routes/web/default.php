<?php

/*
 * The storefront routes of the "default" theme — a small site: the homepage and
 * the standalone pages (About, Contact, FAQ, ...) and nothing else.
 *
 * routes/web.php registers this file behind the 'theme' guard, which 404s any
 * request the active theme has no template for, so these routes answer only
 * while the default theme is the active one. Anything listed in another theme's
 * file 404s here, so there is no /shop, no /cart and no /account on a
 * default-theme site: the request never reaches FrontendController, which is
 * what keeps a small site from quietly growing the ecommerce theme's pages.
 *
 * The homepage and the standalone pages are repeated in the other theme files
 * on purpose: each file is a complete description of that theme's site, so they
 * can be read side by side, and the 'theme' guard is keyed on the route *name*
 * rather than on the file it came from. A shared name has to mean the same page
 * in every file that registers it — the files are kept in step by hand, the same
 * way Themes::ROUTE_TEMPLATES mirrors them for the nav filter.
 */

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [FrontendController::class, 'home']);

// Standalone pages (About, Contact, FAQ, ...) — explicitly whitelisted rather
// than a bare `/{slug}` wildcard so this can never shadow auth/system routes
// (login, dashboard, token, ...) regardless of route registration order.
Route::get('/{slug}', [FrontendController::class, 'page'])
    ->where('slug', 'about|contact|faq')
    ->name('page');
