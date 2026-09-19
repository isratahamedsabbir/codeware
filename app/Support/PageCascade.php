<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PostCategory;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * Every Product/Post/ProductCategory/PostCategory is 1:1 paired with a Page
 * (see each model's page() relation, and Page's product()/post()/category()
 * relations). An orphaned Page (SEO/puck-builder content for an entity that
 * no longer exists) or an orphaned entity (missing its SEO/page-builder
 * record) is never useful, so deleting either side takes the other with it —
 * enforced here from both directions rather than duplicated per call site.
 */
class PageCascade
{
    /**
     * Call when deleting a Product/Post/ProductCategory/PostCategory. Page has
     * no SoftDeletes trait, so $page->delete() is always a permanent delete —
     * leaving it merely soft-deleted would outlive an entity that's gone for
     * good, and its `cms`/`page_revisions` rows cascade with it at the DB level.
     */
    public static function deletePageFor(Model $entity): void
    {
        $entity->page?->delete();
    }

    /**
     * Call when deleting a Page. Resolves the paired entity from the Page's
     * own type/foreign-key columns and deletes it the same way its own
     * Livewire/API delete path would (Product/Post soft-delete; ProductCategory/
     * PostCategory — sharing the `categories` table, distinguished by type —
     * hard-delete since neither uses SoftDeletes).
     */
    public static function deleteEntityFor(Page $page): void
    {
        $entity = match ($page->type) {
            'product' => $page->product,
            'post' => $page->post,
            'product_category' => ProductCategory::find($page->category_id),
            'post_category' => PostCategory::find($page->category_id),
            default => null,
        };

        $entity?->delete();
    }
}
