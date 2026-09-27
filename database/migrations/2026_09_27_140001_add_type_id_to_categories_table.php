<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the `categories.type` string into the two things it was doing at once:
 *
 *  - `kind`  — what kind of row this is (category / tag / brand). Structural,
 *              one of a fixed set, and what ProductCategory / PostCategory /
 *              ProductBrand / Tag each scope their global query on. This is the
 *              old `type` column minus the pool, i.e. product_brand/post_brand/
 *              product/post/tag collapse down to tag or brand, and
 *              product_category/post_category collapse down to category.
 *  - `type_id` — which content pool the row belongs to (Product or Post). A
 *              real foreign key into the new admin-manageable `types` table, so
 *              a pool is a row an admin can edit rather than a hardcoded
 *              literal repeated across five models.
 *
 * A standalone migration rather than folded into
 * 2026_05_27_225520_create_categories_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration would wipe every
 * other table too, discarding real data already created through the admin panel.
 * Same reasoning as 2026_09_13_170734_add_created_by_to_categories_table.php.
 */
return new class extends Migration
{
    /**
     * The two content pools, and the translatable names TypeSeeder creates for
     * them. Inserted here (idempotently) rather than left to the seeder alone,
     * because the backfill below needs their ids to exist — an install upgrading
     * from before TypeSeeder would otherwise point every category, brand and
     * tag at a type row nobody has created yet.
     */
    private const TYPES = [
        ['slug' => 'product', 'name' => ['en' => 'Product', 'bn' => 'পণ্য'], 'sort_order' => 1],
        ['slug' => 'post', 'name' => ['en' => 'Post', 'bn' => 'পোস্ট'], 'sort_order' => 2],
    ];

    public function up(): void
    {
        foreach (self::TYPES as $type) {
            if (DB::table('types')->where('slug', $type['slug'])->exists()) {
                continue;
            }

            DB::table('types')->insert([
                ...$type,
                'name' => json_encode($type['name']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->string('kind', 20)->default('category')->after('type');
            $table->foreignId('type_id')->nullable()->after('parent_id')->constrained('types')->restrictOnDelete();
        });

        $this->backfillKind();
        $this->backfillType();

        Schema::table('categories', function (Blueprint $table) {
            // Both columns are now filled in on every row, so neither is left
            // nullable — a taxonomy row that belongs to no pool, or that isn't
            // one of the three kinds, is not a state the app can act on.
            $table->string('kind', 20)->default('category')->change();
            $table->foreignId('type_id')->nullable(false)->change();

            $table->dropIndex(['type', 'parent_id', 'sort_order']);
            $table->dropIndex(['type']);
            $table->dropColumn('type');

            // Reads of this table are always "these kinds, in this pool, in this
            // order" — same shape the old composite index covered, with the pool
            // now a foreign key.
            $table->index(['kind', 'type_id', 'parent_id', 'sort_order']);
        });
    }

    /**
     * Every existing row has to land in exactly one kind. The references that
     * actually pin a row's identity come first — a row a product points at
     * through brand_id is a brand whatever its `type` string claims — and only
     * then the type string, now read as the two separate things it was doing.
     */
    private function backfillKind(): void
    {
        $this->setKind(DB::table('category_product')->distinct()->pluck('category_id'), 'category');
        $this->setKind(DB::table('posts')->whereNotNull('category_id')->distinct()->pluck('category_id'), 'category');
        $this->setKind(DB::table('products')->whereNotNull('brand_id')->distinct()->pluck('brand_id'), 'brand');
        $this->setKind(DB::table('taggables')->distinct()->pluck('tag_id'), 'tag');

        $this->setKindWhereType(['product_category', 'post_category'], 'category');
        $this->setKindWhereType(['product_brand', 'post_brand'], 'brand');
        $this->setKindWhereType(['post', 'product', 'tag'], 'tag');

        // A null type used to mean "shared across both pools" on brands and tags
        // alike, so it says nothing about which of the two a row is. The logo
        // column does — only brand rows ever carried one — so a leftover shared
        // row with a logo is a brand and one without is a tag. A null-typed row
        // that nothing at all recognises keeps the `category` default.
        DB::table('categories')->whereNull('type')->whereNotNull('logo')->update(['kind' => 'brand']);
    }

    /**
     * @param  Collection<int, int>  $ids
     */
    private function setKind(Collection $ids, string $kind): void
    {
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('categories')->whereIn('id', $ids)->update(['kind' => $kind]);
    }

    /**
     * @param  array<int, string>  $types
     */
    private function setKindWhereType(array $types, string $kind): void
    {
        DB::table('categories')->whereIn('type', $types)->update(['kind' => $kind]);
    }

    private function backfillType(): void
    {
        $productTypeId = DB::table('types')->where('slug', 'product')->value('id');
        $postTypeId = DB::table('types')->where('slug', 'post')->value('id');

        DB::table('categories')->whereIn('type', ['product_category', 'product_brand', 'product'])->update(['type_id' => $productTypeId]);
        DB::table('categories')->whereIn('type', ['post_category', 'post_brand', 'post'])->update(['type_id' => $postTypeId]);

        // A null type used to mean "shared across both pools". That option is
        // gone — every row now belongs to exactly one pool — so the shared rows
        // are filed under Product rather than left unassigned.
        DB::table('categories')->whereNull('type')->update(['type_id' => $productTypeId]);
        DB::table('categories')->where('type', 'tag')->update(['type_id' => $productTypeId]);

        DB::table('categories')->whereNull('type_id')->update(['type_id' => $productTypeId]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('kind');
        });

        // Best-effort reverse mapping. The three brands/tags vocabularies
        // collapsed into a single kind + pool pair, so one row no longer says
        // which of the old strings it used to be — the pool is restored where it
        // is unambiguous and the old shared values are kept for the rest.
        $productTypeId = DB::table('types')->where('slug', 'product')->value('id');
        $postTypeId = DB::table('types')->where('slug', 'post')->value('id');

        $this->setType($productTypeId, 'product', ['category' => 'product_category', 'tag' => 'tag', 'brand' => 'product_brand']);
        $this->setType($postTypeId, 'post', ['category' => 'post_category', 'tag' => 'post', 'brand' => 'post_brand']);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['kind', 'type_id', 'parent_id', 'sort_order']);
            $table->dropConstrainedForeignId('type_id');
            $table->dropColumn('kind');
            $table->index(['type', 'parent_id', 'sort_order']);
            $table->index('type');
        });
    }

    /**
     * @param  array<string, string>  $byKind
     */
    private function setType(?int $typeId, string $fallback, array $byKind): void
    {
        if ($typeId === null) {
            return;
        }

        foreach ($byKind as $kind => $type) {
            DB::table('categories')->where('type_id', $typeId)->where('kind', $kind)->update(['type' => $type]);
        }

        DB::table('categories')->where('type_id', $typeId)->whereNull('type')->update(['type' => $fallback]);
    }
};
