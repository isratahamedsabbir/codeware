<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    private int $authorId;

    /**
     * Demo/dev fake data — products, blog categories, and blog posts, each paired
     * with a Page record the same way saving through the admin form would (see
     * Products/Posts/ProductCategories/PostCategories Form::persist*()) — this
     * seeder inserts rows directly, bypassing those forms, so it has to create
     * the pages itself. Not part of the default install pipeline (see
     * DatabaseSeeder); run explicitly with
     * `php artisan db:seed --class=DemoContentSeeder`.
     */
    public function run(): void
    {
        $this->authorId = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first()?->id ?? User::first()->id;

        $productCategories = ProductCategory::factory()->count(4)->create();
        $productCategories->each(fn (ProductCategory $category) => $this->createPage(
            type: 'product_category',
            keys: ['category_id' => $category->id],
            title: $category->name,
            slug: $this->slugFor($category->getTranslation('name', 'en'), $category->id),
            status: $category->status,
            sortOrder: $category->sort_order,
            description: $category->description,
        ));

        // A deliberately mixed set so the storefront exercises every state:
        // simple products in & out of stock, plus variant products where each
        // combination carries its own stock. `quantity` null/0 means out of
        // stock (see Product::inStock()); variation rows use the stored shape
        // from Admin\Products\Form::cleanedVariations() — a null row quantity
        // means "inherit the base product's stock".
        $productPayloads = [
            // Simple, in stock.
            ['quantity' => 25],
            ['quantity' => 6, 'is_featured' => true],
            ['quantity' => 15, 'price' => 4200, 'discount_price' => 3600],
            ['quantity' => 3],
            // Simple, out of stock.
            ['quantity' => null],
            ['quantity' => 0],
            // Upcoming — not orderable yet.
            ['quantity' => null, 'is_upcoming' => true],
            // Variants: base out of stock, only some combinations carry stock.
            [
                'quantity' => null,
                'variations' => [
                    ['attributes' => ['Color' => 'Red'], 'price' => 3200, 'discount_price' => 2900, 'quantity' => 15, 'visible' => true],
                    ['attributes' => ['Color' => 'Black'], 'price' => 3200, 'quantity' => null, 'visible' => true],
                    ['attributes' => ['Color' => 'Blue'], 'price' => 3350, 'quantity' => 0, 'visible' => true],
                ],
            ],
            // Variants: base in stock, combinations override it (one sold out).
            [
                'quantity' => 40,
                'variations' => [
                    ['attributes' => ['Size' => 'S'], 'price' => 1500, 'quantity' => 10, 'visible' => true],
                    ['attributes' => ['Size' => 'M'], 'price' => 1600, 'discount_price' => 1400, 'quantity' => 5, 'visible' => true],
                    ['attributes' => ['Size' => 'L'], 'price' => 1700, 'quantity' => 0, 'visible' => true],
                    ['attributes' => ['Size' => 'XL'], 'price' => 1800, 'quantity' => 8, 'visible' => true],
                ],
            ],
            // Variants: every combination in stock.
            [
                'quantity' => 20,
                'variations' => [
                    ['attributes' => ['Color' => 'White'], 'price' => 900, 'quantity' => 7, 'visible' => true],
                    ['attributes' => ['Color' => 'Silver'], 'price' => 950, 'quantity' => 4, 'visible' => true],
                ],
            ],
            // Variants: every combination out of stock.
            [
                'quantity' => null,
                'variations' => [
                    ['attributes' => ['Color' => 'Red'], 'price' => 900, 'quantity' => null, 'visible' => true],
                    ['attributes' => ['Color' => 'Black'], 'price' => 900, 'quantity' => 0, 'visible' => true],
                ],
            ],
            // Variants: two attribute axes (Color x Size) combined.
            [
                'quantity' => 30,
                'variations' => [
                    ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 2400, 'quantity' => 6, 'visible' => true],
                    ['attributes' => ['Color' => 'Red', 'Size' => 'L'], 'price' => 2500, 'quantity' => null, 'visible' => true],
                    ['attributes' => ['Color' => 'Black', 'Size' => 'M'], 'price' => 2400, 'quantity' => 0, 'visible' => true],
                    ['attributes' => ['Color' => 'Black', 'Size' => 'L'], 'price' => 2550, 'discount_price' => 2300, 'quantity' => 9, 'visible' => true],
                ],
            ],
            // Variants: combinations inherit the base stock.
            [
                'quantity' => 12,
                'variations' => [
                    ['attributes' => ['Size' => 'S'], 'price' => 500, 'quantity' => null, 'visible' => true],
                    ['attributes' => ['Size' => 'M'], 'price' => 550, 'quantity' => null, 'visible' => true],
                ],
            ],
        ];

        $products = collect($productPayloads)
            ->map(fn (array $payload) => Product::factory()->published()->create($payload));
        $products->each(fn (Product $product) => $product->categories()->attach($productCategories->random()->id));
        $products->random(3)->each(fn (Product $p) => $p->update(['is_featured' => true]));
        $products->each(fn (Product $product) => $this->createPage(
            type: 'product',
            keys: ['product_id' => $product->id],
            title: $product->name,
            slug: $this->slugFor($product->getTranslation('name', 'en'), $product->id),
            status: $product->status,
            sortOrder: $product->sort_order,
            description: $product->description,
        ));

        // Give each demo product a random brand (from ProductBrandSeeder when
        // seeded through DatabaseSeeder, or created ad-hoc below when this
        // seeder runs standalone) so brand pages on the storefront have content.
        $brands = ProductBrand::inRandomOrder()->get();
        if ($brands->isEmpty()) {
            $brands = ProductBrand::factory()->count(3)->create();
        }
        $products->each(fn (Product $product) => $product->update(['brand_id' => $brands->random()->id]));

        // Services have their own slug column (see Service::booted()) — unlike
        // Product/Post/PostCategory above, no paired Page is needed.
        Service::factory()->count(6)->published()->create();

        $postCategories = PostCategory::factory()->count(4)->published()->create();
        $postCategories->each(fn (PostCategory $category) => $this->createPage(
            type: 'post_category',
            keys: ['category_id' => $category->id],
            title: $category->name,
            slug: $this->slugFor($category->getTranslation('name', 'en'), $category->id),
            status: $category->status,
            sortOrder: $category->sort_order,
            description: $category->description,
        ));

        $tags = Tag::factory()->count(6)->published()->create();

        $posts = Post::factory()
            ->count(10)
            ->published()
            ->sequence(fn () => ['category_id' => $postCategories->random()->id])
            ->create(['user_id' => $this->authorId]);
        $posts->each(fn (Post $post) => $post->tags()->attach($tags->random(rand(1, 3))->pluck('id')));
        $posts->each(fn (Post $post) => $this->createPage(
            type: 'post',
            keys: ['post_id' => $post->id],
            title: $post->title,
            slug: $this->slugFor($post->getTranslation('title', 'en'), $post->id),
            status: $post->status,
            description: $post->description,
        ));
    }

    /**
     * @param  array<string, int>  $keys
     * @param  array<string, string>|string  $title
     * @param  array<string, string>|string|null  $description
     */
    private function createPage(string $type, array $keys, array|string $title, string $slug, string $status, ?int $sortOrder = null, array|string|null $description = null): void
    {
        Page::updateOrCreate(
            ['type' => $type, ...$keys],
            array_filter([
                'user_id' => $this->authorId,
                'title' => $title,
                'slug' => $slug,
                'status' => $status,
                'sort_order' => $sortOrder,
                'description' => $description,
            ], fn ($value) => $value !== null),
        );
    }

    /**
     * The entity's own `slug` is a virtual accessor read off its paired Page
     * (see Product/ProductCategory/Post/PostCategory::slug()) — right after
     * `factory()->create()`, before that Page exists, it's always null. The
     * page we're about to create needs a real slug, so derive one straight
     * from the English name/title instead, with the id appended to keep
     * faker-generated names (which can repeat) unique.
     */
    private function slugFor(string $name, int $id): string
    {
        return Str::slug($name).'-'.$id;
    }
}
