<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

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
        $this->authorId = User::where('is_admin', true)->first()?->id ?? User::first()->id;

        $productCategories = ProductCategory::factory()->count(4)->create();
        $productCategories->each(fn (ProductCategory $category) => $this->createPage(
            type: 'product_category',
            keys: ['category_id' => $category->id],
            title: $category->name,
            slug: $category->slug,
            status: $category->status,
            sortOrder: $category->sort_order,
            description: $category->description,
        ));

        $products = Product::factory()
            ->count(12)
            ->published()
            ->sequence(fn () => ['product_category_id' => $productCategories->random()->id])
            ->create();
        $products->random(3)->each(fn (Product $p) => $p->update(['is_featured' => true]));
        $products->each(fn (Product $product) => $this->createPage(
            type: 'product',
            keys: ['product_id' => $product->id],
            title: $product->name,
            slug: $product->slug,
            status: $product->status,
            sortOrder: $product->sort_order,
            description: $product->description,
        ));

        $postCategories = PostCategory::factory()->count(4)->published()->create();
        $postCategories->each(fn (PostCategory $category) => $this->createPage(
            type: 'post_category',
            keys: ['category_id' => $category->id],
            title: $category->name,
            slug: $category->slug,
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
            slug: $post->slug,
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
}
