<?php

namespace Themes\Ecommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersBlog;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's blog: the posts feed and a single post.
 *
 * Structurally the same as Themes/Portfolio\BlogController and deliberately a
 * separate class: the two themes render the same Post rows through their own
 * post.blade.php, and a theme that wanted a different post page should be able
 * to change one without touching the other's file.
 */
class BlogController extends ThemeController
{
    use RendersBlog;
}
