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
            ->map(fn (array $payload) => Product::factory()->published()->create(array_merge(
                $payload,
                $this->demoProductDetails(),
            )));
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
        $posts->each(fn (Post $post) => $post->update(['description' => $this->demoPostDescription()]));
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
     * Dummy rich content (en + bn) for the product additional-data fields —
     * description, short description (`excerpt`) and `specifications`. Stored
     * as HTML the way the admin editors write it, so the ecommerce theme's
     * `rich-text` rendering on the product page shows off its full styling.
     *
     * @return array<string, array{en: string, bn: string}>
     */
    private function demoProductDetails(): array
    {
        $excerpts = [
            'en' => [
                '<p>A <strong>premium-quality</strong> everyday pick, built to last and ready to use.</p>',
                '<p>Thoughtfully designed for comfort, durability and real value — right out of the box.</p>',
                '<p>Dependable, versatile and easy to use — a great addition to your daily routine.</p>',
            ],
            'bn' => [
                '<p>দৈনন্দিন ব্যবহারের জন্য একটি <strong>প্রিমিয়াম মানের</strong> পণ্য — ব্যবহারের জন্য তৈরি।</p>',
                '<p>আরাম, স্থায়িত্ব এবং প্রকৃত মূল্যকে প্রাধান্য দিয়ে সাবধানে ডিজাইন করা হয়েছে।</p>',
                '<p>নির্ভরযোগ্য, বহুমুখী ও ব্যবহারে সহজ — আপনার দৈনন্দিন জীবনের জন্য দারুণ সংযোজন।</p>',
            ],
        ];

        $descriptions = [
            'en' => [
                '<h3>Why you will love it</h3><p>Built from high-grade materials and finished to a high standard, this product is engineered for everyday reliability.</p><p>Setup takes seconds and the compact footprint fits neatly into any space — a solid choice for first-time buyers and upgrades alike.</p><ul><li>Scratch-resistant premium finish</li><li>Simple setup and maintenance</li><li>Energy-efficient operation</li><li>Responsive after-sales support</li></ul>',
                '<h3>Everything you need</h3><p>A well-rounded product that handles the essentials beautifully and stays out of your way when you do not need it.</p><p>Use it alone or pair it with the rest of the range — it is designed to fit right in.</p><ul><li>Quality-checked before shipping</li><li>Compact and lightweight</li><li>Quiet operation</li></ul>',
            ],
            'bn' => [
                '<h3>কেন এটি পছন্দ করবেন</h3><p>উচ্চমানের উপকরণে তৈরি এবং আধুনিক মানদণ্ড অনুযায়ী সমাপ্ত — এই পণ্যটি দৈনন্দিন নির্ভরযোগ্যতার জন্য তৈরি।</p><p>সেটআপ মাত্র কয়েক সেকেন্ড লাগে এবং ছোট আকার যেকোনো জায়গায় সুন্দরভাবে ফিট হয়ে যায়।</p><ul><li>স্ক্র্যাচ-প্রতিরোধী প্রিমিয়াম ফিনিশ</li><li>সহজ সেটআপ ও রক্ষণাবেক্ষণ</li><li>কম বিদ্যুৎ খরচ</li><li>দ্রুত বিক্রয়োত্তর সেবা</li></ul>',
            ],
        ];

        $specifications = [
            'en' => [
                '<ul><li><strong>Dimensions:</strong> 35 × 25 × 12 cm</li><li><strong>Material:</strong> High-grade aluminium alloy</li><li><strong>Weight:</strong> 1.4 kg</li><li><strong>Colors:</strong> Black / Silver</li><li><strong>Warranty:</strong> 12 months</li></ul>',
                '<ul><li><strong>Capacity:</strong> 2.5 L</li><li><strong>Power:</strong> 180 W</li><li><strong>Material:</strong> Food-grade stainless steel</li><li><strong>Dimensions:</strong> 24 × 18 × 16 cm</li><li><strong>Warranty:</strong> 6 months</li></ul>',
            ],
            'bn' => [
                '<ul><li><strong>মাত্রা:</strong> ৩৫ × ২৫ × ১২ সেমি</li><li><strong>উপাদান:</strong> উচ্চমানের অ্যালুমিনিয়াম অ্যালয়</li><li><strong>ওজন:</strong> ১.৪ কেজি</li><li><strong>রং:</strong> কালো / সিলভার</li><li><strong>ওয়ারেন্টি:</strong> ১২ মাস</li></ul>',
            ],
        ];

        return [
            'description' => ['en' => fake()->randomElement($descriptions['en']), 'bn' => fake()->randomElement($descriptions['bn'])],
            'excerpt' => ['en' => fake()->randomElement($excerpts['en']), 'bn' => fake()->randomElement($excerpts['bn'])],
            'specifications' => ['en' => fake()->randomElement($specifications['en']), 'bn' => fake()->randomElement($specifications['bn'])],
        ];
    }

    /**
     * Dummy rich lead (en + bn) for a blog post's `description`.
     *
     * @return array{en: string, bn: string}
     */
    private function demoPostDescription(): array
    {
        return [
            'en' => fake()->randomElement([
                '<p>A short guide to getting the most out of it — what it is, who it is for, and how to start.</p><p>Everything below follows the real-world steps we tested, kept plain and free of jargon.</p>',
                '<p>Quick tips, honest impressions and a few common mistakes to avoid.</p><ul><li>Read the manual before the first use</li><li>Keep it in a dry, cool place</li><li>Clean regularly for best results</li></ul>',
            ]),
            'bn' => '<p>এটির সর্বোচ্চ ব্যবহারের জন্য একটি ছোট গাইড — কী এর জন্য, কাদের জন্য, আর কীভাবে শুরু করবেন।</p><ul><li>সহজ ও জটিলতার বাইরে</li><li>বাস্তব অভিজ্ঞতার ভিত্তিতে</li><li>সাধারণ ভুলগুলো এড়িয়ে চলুন</li></ul>',
        ];
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
