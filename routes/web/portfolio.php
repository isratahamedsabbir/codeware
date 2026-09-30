<?php

/*
 * The storefront routes of the "portfolio" theme — a one-pager, plus the blog.
 *
 * routes/web.php registers this file behind the 'theme' guard, which 404s any
 * request the active theme has no template for, so these routes answer only
 * while the portfolio theme is the active one. There is no /shop, no /cart and
 * no /{slug} standalone-page route for it to own: the portfolio's nav is section
 * anchors ("#projects", "#skills", see Frontend::portfolioMenuItems()) into the
 * single page below, and asking a portfolio site for /about gets the portfolio's
 * own 404 — the same one a genuinely missing page gets. This file registering
 * only two routes is what makes that true; there is no Portfolio\ShopController
 * or Portfolio\PageController for it to reach by mistake.
 *
 * Both controllers are this theme's own, in app/Http/Controllers/Themes/Portfolio/
 * — the fourth per-theme half, after the route file (this one), the templates
 * (resources/views/frontend/themes/portfolio/) and the stylesheet
 * (resources/css/themes/portfolio/theme.css).
 *
 * The blog is the one thing here that is not a one-pager section, because a post
 * is its own page. The names and the Post rows are the ecommerce theme's, but
 * the layout is this theme's own post.blade.php: `blog` and `blog.post` are names
 * shared across theme files on purpose, and the 'theme' guard is keyed on the
 * name, so the pair moves together — this theme answering /blog only works
 * because it ships blog.blade.php and post.blade.php. See routes/web/default.php.
 *
 * The homepage is repeated in the other theme files on purpose: each file is a
 * complete description of that theme's site, so they can be read side by side.
 * See routes/web/default.php.
 */

use App\Http\Controllers\Themes\Portfolio\BlogController;
use App\Http\Controllers\Themes\Portfolio\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it
// keeps the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [HomeController::class, 'home']);

// Blog — the public posts feed (listing + single post), gated by the same "blog"
// feature flag as the admin's Posts module and behind the same 'theme' guard, so
// /blog on an ecommerce site is that theme's page and on a portfolio site is this
// one. Post slugs, exactly like product slugs, live on each post's paired Page,
// so the detail route is resolved by the Page's slug.
Route::middleware('feature:blog')->group(function () {
    Route::get('/blog', [BlogController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [BlogController::class, 'post'])->name('blog.post');
});
