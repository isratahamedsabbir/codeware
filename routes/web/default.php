<?php

/*
 * The storefront routes of the "default" theme — a small site: the homepage and
 * the standalone pages (About, Contact, FAQ, ...) and nothing else.
 *
 * routes/web.php registers this file behind the 'theme' guard, which 404s any
 * request the active theme has no template for, so these routes answer only
 * while the default theme is the active one. Anything listed in another theme's
 * file 404s here, so there is no /shop, no /cart and no /account on a
 * default-theme site: the request never reaches Themes\Default\HomeController,
 * which is what keeps a small site from quietly growing the ecommerce theme's
 * pages.
 *
 * The controllers are this theme's own, in app/Http/Controllers/Themes/Default/
 * — the fourth per-theme half, after the route file (this one), the templates
 * (resources/views/frontend/themes/default/) and the stylesheet
 * (resources/css/themes/default/theme.css). Every line here points at a class
 * inside this theme's folder, so what this theme's site is made of is readable
 * without opening a file named after the application.
 *
 * The homepage and the standalone pages are repeated in the other theme files
 * on purpose: each file is a complete description of that theme's site, so they
 * can be read side by side, and the 'theme' guard is keyed on the route *name*
 * rather than on the file it came from. A shared name has to mean the same page
 * in every file that registers it — the files are kept in step by hand, the same
 * way Themes::ROUTE_TEMPLATES mirrors them for the nav filter.
 */

use App\Http\Controllers\Themes\Default\HomeController;
use App\Http\Controllers\Themes\Default\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [HomeController::class, 'home']);

// Standalone pages (About, Contact, FAQ, ...) — explicitly whitelisted rather
// than a bare `/{slug}` wildcard so this can never shadow auth/system routes
// (login, dashboard, token, ...) regardless of route registration order. A
// theme with no page.blade.php (the portfolio) registers no `page` route, so
// this one is simply never matched there.
Route::get('/{slug}', [PageController::class, 'page'])
    ->where('slug', 'about|contact|faq')
    ->name('page');
