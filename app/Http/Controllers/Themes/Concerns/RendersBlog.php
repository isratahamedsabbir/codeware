<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\CmsSection;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * The blog feed and a single post.
 *
 * Unlike the catalog traits, this one is not ecommerce-only: the ecommerce and
 * portfolio themes both ship a blog, and they render the same Post rows through
 * their own templates. So the query and the view data are shared here, and the
 * two themes' route files each point `blog` and `blog.post` at their own
 * controller that mixes this in — which is what lets a theme override a post
 * page without affecting the other.
 */
trait RendersBlog
{
    /**
     * The blog feed — every published post, newest first, optionally narrowed
     * to a category via ?category (category links come from the category's
     * paired Page slug). Rendered by the active theme's own blog template, and
     * 404s on a theme that ships none.
     */
    public function blog(Request $request)
    {
        $posts = Post::published()
            ->with(['page', 'category.page', 'user:id,name', 'tags'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($slug = $request->query('category')) {
            $posts->whereHas('category.page', fn ($q) => $q->where('slug', $slug));
        }

        $categories = PostCategory::where('status', 'active')
            ->with('page')
            ->withCount(['posts' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (PostCategory $category) => $category->page !== null);

        return $this->view('blog', [
            'posts' => $posts->paginate(Setting::perPage())->withQueryString(),
            'categories' => $categories,
            'title' => __('Blog'),
            'currentSlug' => 'blog',
        ]);
    }

    /**
     * A single blog post — title, meta (author, date, reading time), category,
     * tags, and the post's paired Page's CMS sections (Puck-built content lives
     * on the Page, just like products). Resolved by the paired Page's slug.
     */
    public function post(string $slug)
    {
        $post = Post::published()
            ->with(['page', 'category.page', 'user:id,name', 'tags'])
            ->whereHas('page', fn ($q) => $q->where('slug', $slug))
            ->first();

        abort_unless($post, 404, 'Unknown post.');

        $this->countView($post);

        $related = Post::published()
            ->with('page')
            ->whereHas('page')
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return $this->view('post', [
            'post' => $post,
            'related' => $related,
            'sections' => $post->page ? CmsSection::cachedForPage($post->page->id) : collect(),
            'page' => $post->page,
            'title' => $post->page?->seo_title ?: $post->getTranslation('title', 'en', false),
            'currentSlug' => $post->page?->slug ?? 'blog',
        ]);
    }
}
