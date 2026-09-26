<?php

/*
 * The storefront routes of the "portfolio" theme — which is a one-pager, so that
 * is the whole of it.
 *
 * routes/web.php registers this file behind the 'theme' guard, which 404s any
 * request the active theme has no template for, so these routes answer only
 * while the portfolio theme is the active one. There is no /shop, no /blog and
 * no /{slug} standalone-page route for it to own: the portfolio's nav is section
 * anchors ("#projects", "#skills", see Frontend::portfolioMenuItems()) into the
 * single page below, and asking a portfolio site for /about gets the portfolio's
 * own 404 — the same one a genuinely missing page gets.
 *
 * The homepage is repeated in the other theme files on purpose: each file is a
 * complete description of that theme's site, so they can be read side by side.
 * See routes/web/default.php.
 */

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [FrontendController::class, 'home']);
