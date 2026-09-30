<?php

namespace App\Http\Controllers\Themes\Portfolio;

use App\Http\Controllers\Themes\Concerns\RendersBlog;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "portfolio" theme's blog: the posts feed and a single post.
 *
 * The one thing here that is not a one-pager section, because a post is its own
 * page. The routes and the Post rows are the ecommerce theme's, but the
 * template is this theme's own post.blade.php — so a portfolio blog post is
 * laid out like the rest of the portfolio rather than like a shop page, and the
 * two themes' blog files can diverge without either one reaching into the
 * other's controller.
 */
class BlogController extends ThemeController
{
    use RendersBlog;
}
