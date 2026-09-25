<?php

namespace Database\Seeders;

use App\Models\Advertisement;
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
        $posts->each(fn (Post $post) => $post->update([
            'description' => $this->demoPostDescription(),
            // Dummy thumbnail so post cards actually show an image in fresh
            // dev installs (no local files shipped); swap for a library image
            // from the admin form's Thumbnail Image picker.
            'featured_image' => "https://picsum.photos/seed/codeware-post-{$post->id}/1200/675",
        ]));
        $posts->each(fn (Post $post) => $post->tags()->attach($tags->random(rand(1, 3))->pluck('id')));
        $posts->each(fn (Post $post) => $this->createPage(
            type: 'post',
            keys: ['post_id' => $post->id],
            title: $post->title,
            slug: $this->slugFor($post->getTranslation('title', 'en'), $post->id),
            status: $post->status,
            description: $post->description,
        ));

        // Two demo banners so the product-page "Sponsored" slot has something to
        // show: one live now, one scheduled to start later (admin sees its
        // "Scheduled" status). Images are stable remote placeholders — there are
        // no local files in a fresh dev install — and can be swapped from the
        // Media Library in the admin form.
        Advertisement::create([
            'name' => 'Summer Sale — 15% Off Everything',
            'image' => 'https://picsum.photos/seed/codeware-summer-sale/600/750',
            'url' => url('/shop'),
            'valid_from' => now()->subDays(2)->startOfDay(),
            'valid_until' => now()->addDays(60)->endOfDay(),
        ]);

        Advertisement::create([
            'name' => 'Winter Collection — Coming Soon',
            'image' => 'https://picsum.photos/seed/codeware-winter/600/750',
            'url' => url('/shop'),
            'valid_from' => now()->addDays(5)->startOfDay(),
            'valid_until' => now()->addDays(90)->endOfDay(),
        ]);
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
     * Dummy long-form article (en + bn) for a blog post's `description` — at
     * least 1000 words per locale, so the blog detail page shows a realistic
     * full-length read. Built from a shuffled pool of sections (so posts don't
     * all read identically), stopped once the word target is reached.
     *
     * @return array{en: string, bn: string}
     */
    private function demoPostDescription(): array
    {
        return [
            'en' => $this->buildArticle(self::postSectionsEn(), 'Final thoughts', 'Choosing well is less about finding the perfect product and more about understanding what you actually need. Take your time, compare honestly, look after what you buy and it will look after you in return. We hope this guide makes your next decision a little easier and a lot more confident. If you have questions or your own experiences to share, leave a comment below — we read every one of them and often use your feedback to improve our future guides.'),
            'bn' => $this->buildArticle(self::postSectionsBn(), 'শেষ কথা', 'ভালো কেনাকাটা মানে নিখুঁত পণ্য খুঁজে বের করা নয়, বরং নিজের আসল প্রয়োজনটা ঠিকভাবে বোঝা। সময় নিন, সৎভাবে তুলনা করুন, কেনা জিনিসের যত্ন নিন — তাহলে সেটিও দীর্ঘদিন আপনাকে ভালো সেবা দেবে। আশা করি এই গাইডটি আপনার পরবর্তী সিদ্ধান্তকে একটু সহজ আর অনেক বেশি আত্মবিশ্বাসী করে তুলবে। আপনার কোনো প্রশ্ন বা নিজের অভিজ্ঞতা থাকলে নিচে মন্তব্য করে জানান — আমরা প্রতিটি মন্তব্য পড়ি এবং ভবিষ্যতের গাইড আরও ভালো করতে আপনার মতামত কাজে লাগাই।'),
        ];
    }

    /**
     * Shuffles the section pool and appends sections until the article passes
     * 1000 words, then closes with the conclusion. Words are counted on
     * whitespace (str_word_count() doesn't understand Bangla script).
     *
     * @param  list<array{0: string, 1: string}>  $sections
     */
    private function buildArticle(array $sections, string $conclusionHeading, string $conclusion, int $minWords = 1000): string
    {
        $countWords = fn (string $html) => count(preg_split('/\s+/u', trim(strip_tags(str_replace('<', ' <', $html))), -1, PREG_SPLIT_NO_EMPTY));
        $closing = "<h2>{$conclusionHeading}</h2><p>{$conclusion}</p>";

        $html = '';
        foreach (fake()->shuffleArray($sections) as [$heading, $body]) {
            $html .= "<h2>{$heading}</h2>{$body}";

            if ($countWords($html.$closing) >= $minWords) {
                break;
            }
        }

        return $html.$closing;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function postSectionsEn(): array
    {
        return [
            ['Why this topic matters', '<p>Every day we make dozens of small buying decisions, and most of them barely register. Yet the products we bring home shape our routines, our budgets and even our moods. A well-chosen tool saves minutes every single day; a poor one quietly drains time, money and patience for years. That is why we believe it is worth slowing down and thinking a little harder before adding something new to the cart.</p><p>In this guide we walk through the questions we ask ourselves before every purchase, the mistakes we have made along the way, and the habits that have helped us get far more value out of the things we already own. None of it requires expert knowledge — just a bit of curiosity and honesty about how you really live.</p>'],
            ['Start with the problem, not the product', '<p>The most common mistake shoppers make is falling in love with a product before they have clearly defined the problem it should solve. A glossy photo or a limited-time discount can easily push the real question aside. Before you browse, write down in one sentence what you want to be different after the purchase. Do you want to cook faster on weekdays? Do you need a laptop that survives a full day of classes on one charge?</p><p>Once the problem is written down, every option you look at can be measured against it. Features that do not move you closer to that goal stop looking so attractive, and the choice becomes surprisingly simple. You will also find it much easier to walk away from impulse buys that do not serve any real need.</p>'],
            ['Set a realistic budget', '<p>A budget is not just a ceiling; it is a tool for focus. Decide on a range before you start comparing, and include the hidden costs that often get forgotten: delivery, accessories, installation, consumables and future repairs. A cheaper item that needs expensive refills can end up costing more within a year than a pricier model that runs on almost nothing.</p><p>It also helps to think in terms of cost per use. A pair of shoes worn every day for two years is often better value than a bargain pair that falls apart in three months. When you divide the price by how often you will realistically use something, many decisions make themselves.</p><ul><li>List every cost, not just the sticker price</li><li>Estimate how often you will use it</li><li>Leave a small buffer for surprises</li></ul>'],
            ['Research without drowning in information', '<p>Online research is powerful, but it can also be paralysing. There are endless reviews, videos and comparison charts, and many of them contradict each other. Our advice is to limit yourself to a handful of trustworthy sources: detailed reviews from people who actually used the product for weeks, the official specification sheet, and a few comments from real buyers with similar needs to yours.</p><p>Pay special attention to negative reviews that describe specific problems rather than general frustration. A comment such as "the hinge loosened after four months" tells you far more than "terrible product, do not buy". If the same concrete issue shows up again and again, take it seriously. If it appears only once, it may simply be bad luck.</p>'],
            ['Quality you can actually see and feel', '<p>Specifications only tell part of the story. Whenever possible, look closely at how a product is built. Are the seams even? Do moving parts feel solid, or do they wobble and creak? Is the material pleasant to touch, and does it look like it will age gracefully rather than peel or crack? These details are often better predictors of long-term satisfaction than any number printed on the box.</p><p>When shopping online, zoom into product photos, read the materials list carefully and check the return policy before ordering. A generous and clear return window is itself a sign that a seller is confident in what they offer. It also gives you the freedom to inspect the item properly at home without worrying about being stuck with it.</p>'],
            ['Think about the whole life of the product', '<p>A purchase does not end at checkout. Consider how the product will be cleaned, maintained, repaired and eventually disposed of. Can you easily find spare parts? Is there a local service centre, or will a small fault mean sending it back across the country? Does the manufacturer publish manuals and offer software updates, or does support disappear after the first year?</p><p>Products designed to be repaired tend to last longer and hold their value better. They are also kinder to the environment, because fewer items end up in landfill. Asking these questions before you buy takes only a few minutes, but it can save you from frustration and extra expense later on.</p><ul><li>Check warranty length and what it covers</li><li>Look for availability of spare parts</li><li>Read about the brand\'s after-sales service</li></ul>'],
            ['Common mistakes to avoid', '<p>Even experienced shoppers fall into the same traps. Buying the biggest or most powerful version "just in case" is one of them — extra capacity costs money, takes up space and often goes unused. Another is trusting a single enthusiastic review, especially if it reads more like an advertisement than an honest experience. Rushing because of a countdown timer is a third; genuine deals come around far more often than the timer suggests.</p><p>Finally, many people forget to check compatibility. A new accessory that does not fit your existing device, or a charger with the wrong connector, turns a simple purchase into a chain of extra orders. A quick look at the compatibility list before paying avoids all of that hassle.</p>'],
            ['Getting started after your purchase', '<p>When the parcel arrives, resist the urge to tear everything open and start immediately. Check that all listed parts are included, keep the packaging for the duration of the return period, and take a moment to read the quick-start guide. It is tempting to skip, but those few pages usually contain the one setting or step that makes the biggest difference to performance.</p><p>Register the product with the manufacturer if a warranty depends on it, and store the receipt somewhere you will find it again — a photo in a dedicated folder on your phone works well. These small steps take five minutes and can make any future claim or repair dramatically easier.</p>'],
            ['Everyday care and maintenance', '<p>Most products fail early not because they were poorly made, but because they were never looked after. Dust, moisture, heat and neglect are the silent enemies of almost everything we own. A short routine — wiping surfaces, clearing filters, tightening screws, updating software — can add years to a product\'s life and keep it performing like new.</p><p>Set a simple reminder once a month to check the things you use most. It feels like a chore at first, but it quickly becomes a habit, and the reward is fewer breakdowns, better performance and a noticeably longer time before you need to replace anything.</p><ul><li>Clean regularly with the recommended materials</li><li>Store in a dry, cool place away from direct sunlight</li><li>Install updates and replace worn parts promptly</li></ul>'],
            ['Getting more value from what you own', '<p>Before buying something new, it is worth asking whether an item you already own could do the job with a small upgrade or a bit of care. A fresh battery, a new set of tyres, a replacement cable or a professional service can often revive something you were about to throw away. The savings are real, and the satisfaction of extending the life of a trusted possession is surprisingly rewarding.</p><p>When you do replace something, consider selling, donating or recycling the old one. Someone else may be delighted to give it a second life, and you will recover a little of the cost while keeping useful items out of the waste stream.</p>'],
            ['How we test and review', '<p>Our recommendations come from hands-on use rather than press releases. Whenever we can, we use each product in normal daily life for several weeks, noting what works, what annoys us and what changes over time. We compare it against alternatives at similar prices and pay close attention to comfort, reliability, ease of use and real-world running costs.</p><p>We also read feedback from our customers and update our guides when a product changes or a better option appears. If something we recommended starts to disappoint people, we want to know about it. Honest, practical advice matters more to us than any single sale, and we hope it shows in everything we write.</p>'],
            ['A quick checklist before you buy', '<p>If you remember nothing else from this article, keep this short checklist in mind. It takes less than a minute to run through, and it will catch most of the mistakes that lead to regret. Print it, save it as a note on your phone, or simply ask yourself these questions every time you are about to click the buy button.</p><ul><li>Have I clearly defined the problem this solves?</li><li>Does it fit my budget, including running costs?</li><li>Have I read a few detailed, honest reviews?</li><li>Is it compatible with what I already own?</li><li>Is the warranty and return policy acceptable?</li><li>Will I realistically use it often enough?</li></ul><p>If the answer to every question is a confident yes, go ahead and enjoy your new purchase.</p>'],
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function postSectionsBn(): array
    {
        return [
            ['কেন এই বিষয়টি গুরুত্বপূর্ণ', '<p>প্রতিদিন আমরা অসংখ্য ছোট ছোট কেনাকাটার সিদ্ধান্ত নিই, যার বেশিরভাগই আমাদের চোখে পড়ে না। অথচ যে পণ্যগুলো আমরা ঘরে আনি, সেগুলোই আমাদের দৈনন্দিন রুটিন, বাজেট এমনকি মেজাজকেও প্রভাবিত করে। ভালোভাবে বেছে নেওয়া একটি জিনিস প্রতিদিন কয়েক মিনিট সময় বাঁচায়; আর ভুলভাবে কেনা একটি জিনিস বছরের পর বছর নীরবে সময়, টাকা আর ধৈর্য নষ্ট করে। তাই নতুন কিছু কার্টে যোগ করার আগে একটু থেমে ভাবা সত্যিই মূল্যবান।</p><p>এই গাইডে আমরা সেই প্রশ্নগুলো নিয়ে আলোচনা করব যেগুলো আমরা প্রতিটি কেনাকাটার আগে নিজেদের জিজ্ঞেস করি, পথে যে ভুলগুলো করেছি, আর যে অভ্যাসগুলো আমাদের কেনা জিনিস থেকে অনেক বেশি সুবিধা পেতে সাহায্য করেছে। এর জন্য কোনো বিশেষজ্ঞ জ্ঞানের দরকার নেই — শুধু একটু কৌতূহল আর নিজের জীবনযাপন নিয়ে সৎ থাকা।</p>'],
            ['পণ্য নয়, সমস্যা দিয়ে শুরু করুন', '<p>ক্রেতারা সবচেয়ে বেশি যে ভুলটি করেন তা হলো, কোন সমস্যার সমাধান দরকার সেটা পরিষ্কারভাবে ঠিক করার আগেই একটি পণ্যের প্রেমে পড়ে যাওয়া। একটি চকচকে ছবি বা সীমিত সময়ের ছাড় খুব সহজেই আসল প্রশ্নটিকে পাশে সরিয়ে দেয়। ব্রাউজ করার আগে এক বাক্যে লিখে ফেলুন, কেনার পর আপনি কী পরিবর্তন চান। সপ্তাহের দিনগুলোতে দ্রুত রান্না করতে চান? নাকি এমন একটি ল্যাপটপ দরকার যা এক চার্জে সারাদিন ক্লাস চালিয়ে নিতে পারে?</p><p>সমস্যাটি লিখে ফেলার পর প্রতিটি বিকল্পকে সেই মানদণ্ডে যাচাই করা যায়। যে ফিচারগুলো আপনাকে লক্ষ্যের কাছে নিয়ে যায় না, সেগুলো আর তেমন আকর্ষণীয় মনে হয় না, এবং সিদ্ধান্ত নেওয়া আশ্চর্যজনকভাবে সহজ হয়ে যায়। অপ্রয়োজনীয় হঠাৎ কেনাকাটা থেকেও তখন সহজে বিরত থাকা যায়।</p>'],
            ['বাস্তবসম্মত বাজেট ঠিক করুন', '<p>বাজেট শুধু খরচের সর্বোচ্চ সীমা নয়; এটি মনোযোগ ধরে রাখার একটি হাতিয়ার। তুলনা শুরু করার আগেই একটি দামের পরিসীমা ঠিক করুন, আর সেই লুকানো খরচগুলোও হিসাবে রাখুন যেগুলো প্রায়ই ভুলে যাওয়া হয়: ডেলিভারি চার্জ, আনুষঙ্গিক জিনিস, ইনস্টলেশন, নিয়মিত ব্যবহার্য উপকরণ এবং ভবিষ্যতের মেরামত। কম দামের একটি জিনিস যদি দামি রিফিল দাবি করে, তাহলে এক বছরের মধ্যেই সেটি বেশি দামি কিন্তু সাশ্রয়ী মডেলের চেয়ে বেশি খরচ করিয়ে ফেলতে পারে।</p><p>প্রতি ব্যবহারে খরচ হিসাব করাও খুব কাজে দেয়। দুই বছর ধরে প্রতিদিন পরা এক জোড়া জুতা প্রায়ই তিন মাসে ছিঁড়ে যাওয়া সস্তা জুতার চেয়ে বেশি লাভজনক।</p><ul><li>শুধু দাম নয়, সব খরচের তালিকা করুন</li><li>কতবার ব্যবহার করবেন তা অনুমান করুন</li><li>অপ্রত্যাশিত খরচের জন্য কিছু টাকা রাখুন</li></ul>'],
            ['তথ্যের ভিড়ে না হারিয়ে গবেষণা করুন', '<p>অনলাইন গবেষণা খুবই শক্তিশালী, কিন্তু এটি মানুষকে বিভ্রান্তও করে দিতে পারে। অগণিত রিভিউ, ভিডিও আর তুলনামূলক চার্ট রয়েছে, যার অনেকগুলোই একে অপরের বিপরীত কথা বলে। আমাদের পরামর্শ হলো কয়েকটি বিশ্বস্ত উৎসের মধ্যেই সীমাবদ্ধ থাকা: যারা কয়েক সপ্তাহ ধরে পণ্যটি সত্যিই ব্যবহার করেছেন তাদের বিস্তারিত রিভিউ, অফিসিয়াল স্পেসিফিকেশন, আর আপনার মতো প্রয়োজন আছে এমন কিছু প্রকৃত ক্রেতার মন্তব্য।</p><p>যেসব নেতিবাচক রিভিউ সাধারণ বিরক্তির বদলে নির্দিষ্ট সমস্যার কথা বলে, সেগুলোর দিকে বিশেষ নজর দিন। "চার মাস পর কবজা ঢিলে হয়ে গেছে" — এমন একটি মন্তব্য "বাজে পণ্য, কিনবেন না" কথাটির চেয়ে অনেক বেশি তথ্য দেয়। একই নির্দিষ্ট সমস্যা বারবার দেখা গেলে সেটিকে গুরুত্ব দিন।</p>'],
            ['যে মান চোখে দেখা ও হাতে অনুভব করা যায়', '<p>স্পেসিফিকেশন পুরো গল্পের একটি অংশ মাত্র। সম্ভব হলে পণ্যটি কীভাবে তৈরি হয়েছে তা কাছ থেকে দেখুন। সেলাই কি সমান? নড়াচড়া করা অংশগুলো কি মজবুত মনে হয়, নাকি নড়বড়ে আর শব্দ করে? উপাদানটি কি ছুঁয়ে আরাম লাগে, আর দেখে কি মনে হয় সময়ের সঙ্গে সুন্দরভাবে টিকে থাকবে, খসে বা ফেটে যাবে না? বাক্সে লেখা যেকোনো সংখ্যার চেয়ে এই খুঁটিনাটি বিষয়গুলো প্রায়ই দীর্ঘমেয়াদি সন্তুষ্টির ভালো পূর্বাভাস দেয়।</p><p>অনলাইনে কেনার সময় পণ্যের ছবি জুম করে দেখুন, উপাদানের তালিকা মনোযোগ দিয়ে পড়ুন এবং অর্ডার করার আগে রিটার্ন পলিসি যাচাই করুন। উদার ও স্পষ্ট রিটার্ন সুবিধা নিজেই বোঝায় যে বিক্রেতা তার পণ্যের ব্যাপারে আত্মবিশ্বাসী। এতে ঘরে বসে নিশ্চিন্তে পণ্যটি যাচাই করার সুযোগও পাওয়া যায়।</p>'],
            ['পণ্যের পুরো জীবনকাল নিয়ে ভাবুন', '<p>চেকআউটেই কেনাকাটা শেষ হয় না। পণ্যটি কীভাবে পরিষ্কার, রক্ষণাবেক্ষণ ও মেরামত করা হবে এবং শেষ পর্যন্ত কীভাবে ফেলে দেওয়া হবে — সেটাও ভাবুন। খুচরা যন্ত্রাংশ কি সহজে পাওয়া যায়? কাছাকাছি কোনো সার্ভিস সেন্টার আছে, নাকি ছোট একটি ত্রুটির জন্যও দেশের অন্য প্রান্তে পাঠাতে হবে? প্রস্তুতকারক কি ম্যানুয়াল প্রকাশ করে ও সফটওয়্যার আপডেট দেয়, নাকি প্রথম বছরের পর সহায়তা হারিয়ে যায়?</p><p>যে পণ্যগুলো মেরামতের উপযোগী করে তৈরি, সেগুলো সাধারণত বেশিদিন টেকে এবং মূল্যও ভালোভাবে ধরে রাখে। পরিবেশের জন্যও এগুলো ভালো, কারণ কম জিনিস আবর্জনায় যায়।</p><ul><li>ওয়ারেন্টির মেয়াদ ও আওতা যাচাই করুন</li><li>খুচরা যন্ত্রাংশের সহজলভ্যতা দেখুন</li><li>ব্র্যান্ডের বিক্রয়োত্তর সেবা সম্পর্কে জানুন</li></ul>'],
            ['যে ভুলগুলো এড়িয়ে চলবেন', '<p>অভিজ্ঞ ক্রেতারাও প্রায়ই একই ফাঁদে পা দেন। "যদি লাগে" ভেবে সবচেয়ে বড় বা সবচেয়ে শক্তিশালী সংস্করণ কেনা তার একটি — বাড়তি ক্ষমতার জন্য বেশি টাকা লাগে, বেশি জায়গা লাগে এবং প্রায়ই তা অব্যবহৃত থেকে যায়। আরেকটি ভুল হলো শুধু একটি উচ্ছ্বসিত রিভিউয়ের ওপর ভরসা করা, বিশেষ করে সেটি যদি সৎ অভিজ্ঞতার চেয়ে বিজ্ঞাপনের মতো শোনায়। কাউন্টডাউন টাইমার দেখে তাড়াহুড়া করা তৃতীয় ভুল; আসল অফার টাইমারের ইঙ্গিতের চেয়ে অনেক বেশি বার আসে।</p><p>সবশেষে, অনেকেই সামঞ্জস্যতা যাচাই করতে ভুলে যান। নতুন একটি আনুষঙ্গিক জিনিস যদি আপনার বর্তমান ডিভাইসে না মেলে, বা চার্জারের কানেক্টর ভুল হয়, তাহলে একটি সাধারণ কেনাকাটা একাধিক বাড়তি অর্ডারে পরিণত হয়। টাকা দেওয়ার আগে একবার তালিকাটি দেখে নিলেই এসব ঝামেলা এড়ানো যায়।</p>'],
            ['কেনার পর যেভাবে শুরু করবেন', '<p>পার্সেল হাতে পেলে সঙ্গে সঙ্গে সবকিছু খুলে ব্যবহার শুরু করার তাড়নাটা একটু সামলান। তালিকাভুক্ত সব অংশ আছে কিনা মিলিয়ে নিন, রিটার্নের মেয়াদ পর্যন্ত প্যাকেজিং রেখে দিন এবং কুইক-স্টার্ট গাইডটি একবার পড়ে নিন। এটি এড়িয়ে যেতে ইচ্ছা করে, কিন্তু ওই কয়েকটি পৃষ্ঠাতেই সাধারণত এমন একটি সেটিং বা ধাপ থাকে যা পারফরম্যান্সে সবচেয়ে বড় পার্থক্য আনে।</p><p>ওয়ারেন্টি পেতে প্রয়োজন হলে প্রস্তুতকারকের কাছে পণ্যটি নিবন্ধন করুন, আর রসিদটি এমন জায়গায় রাখুন যেখানে পরে সহজে খুঁজে পাবেন — ফোনে একটি আলাদা ফোল্ডারে ছবি তুলে রাখা বেশ কার্যকর। এই ছোট ধাপগুলোতে মাত্র পাঁচ মিনিট লাগে, কিন্তু ভবিষ্যতের যেকোনো দাবি বা মেরামত অনেক সহজ হয়ে যায়।</p>'],
            ['দৈনন্দিন যত্ন ও রক্ষণাবেক্ষণ', '<p>বেশিরভাগ পণ্য আগেভাগে নষ্ট হয় খারাপভাবে তৈরি হওয়ার কারণে নয়, বরং কখনো যত্ন না নেওয়ার কারণে। ধুলা, আর্দ্রতা, তাপ আর অবহেলা — এগুলোই আমাদের প্রায় সব জিনিসের নীরব শত্রু। একটি ছোট রুটিন — পৃষ্ঠতল মোছা, ফিল্টার পরিষ্কার করা, স্ক্রু টাইট করা, সফটওয়্যার আপডেট করা — একটি পণ্যের আয়ু কয়েক বছর বাড়িয়ে দিতে পারে এবং নতুনের মতো কাজ করাতে পারে।</p><p>সবচেয়ে বেশি ব্যবহৃত জিনিসগুলো পরীক্ষা করার জন্য মাসে একবার একটি সহজ রিমাইন্ডার সেট করুন। প্রথমে এটি একটি ঝামেলা মনে হবে, কিন্তু দ্রুতই অভ্যাসে পরিণত হবে।</p><ul><li>প্রস্তাবিত উপকরণ দিয়ে নিয়মিত পরিষ্কার করুন</li><li>সরাসরি রোদ থেকে দূরে শুকনো ও ঠান্ডা জায়গায় রাখুন</li><li>আপডেট ইনস্টল করুন এবং ক্ষয়ে যাওয়া অংশ দ্রুত বদলান</li></ul>'],
            ['নিজের জিনিস থেকে আরও বেশি সুবিধা নিন', '<p>নতুন কিছু কেনার আগে একবার ভাবুন, আপনার কাছে থাকা কোনো জিনিস কি সামান্য আপগ্রেড বা একটু যত্নেই কাজটি করে দিতে পারে? একটি নতুন ব্যাটারি, নতুন টায়ার, একটি বদলি কেবল বা একটি পেশাদার সার্ভিস প্রায়ই এমন জিনিসকে নতুন জীবন দিতে পারে যা আপনি ফেলে দিতে যাচ্ছিলেন। এতে সত্যিকারের সাশ্রয় হয়, আর প্রিয় একটি জিনিসের আয়ু বাড়ানোর তৃপ্তিও কম নয়।</p><p>যখন সত্যিই কিছু বদলাবেন, তখন পুরোনোটি বিক্রি, দান বা রিসাইকেল করার কথা ভাবুন। অন্য কেউ হয়তো আনন্দের সঙ্গে সেটিকে দ্বিতীয় জীবন দেবে, আপনিও খরচের কিছুটা ফেরত পাবেন, আর কাজের জিনিসগুলো আবর্জনায় যাওয়া থেকে রক্ষা পাবে।</p>'],
            ['আমরা যেভাবে পরীক্ষা ও রিভিউ করি', '<p>আমাদের সুপারিশগুলো প্রেস রিলিজ থেকে নয়, বাস্তব ব্যবহার থেকে আসে। যখনই সম্ভব, আমরা প্রতিটি পণ্য কয়েক সপ্তাহ ধরে স্বাভাবিক দৈনন্দিন জীবনে ব্যবহার করি এবং লক্ষ করি কী ভালো কাজ করে, কী বিরক্ত করে আর সময়ের সঙ্গে কী বদলায়। একই দামের বিকল্পগুলোর সঙ্গে তুলনা করি এবং আরাম, নির্ভরযোগ্যতা, ব্যবহারের সহজতা ও বাস্তব চলমান খরচের দিকে বিশেষ নজর দিই।</p><p>আমরা গ্রাহকদের মতামতও পড়ি এবং কোনো পণ্য বদলালে বা আরও ভালো বিকল্প এলে আমাদের গাইড হালনাগাদ করি। আমাদের সুপারিশ করা কোনো পণ্য যদি মানুষকে হতাশ করতে শুরু করে, আমরা সেটা জানতে চাই। কোনো একক বিক্রির চেয়ে সৎ ও বাস্তবসম্মত পরামর্শ আমাদের কাছে বেশি গুরুত্বপূর্ণ।</p>'],
            ['কেনার আগে একটি দ্রুত চেকলিস্ট', '<p>এই লেখা থেকে যদি আর কিছু মনে না-ও থাকে, এই ছোট চেকলিস্টটি মনে রাখুন। এটি দেখে নিতে এক মিনিটও লাগে না, আর যে ভুলগুলো পরে আফসোসের কারণ হয় তার বেশিরভাগই এতে ধরা পড়ে। এটি প্রিন্ট করুন, ফোনে নোট হিসেবে সেভ করুন, অথবা প্রতিবার কেনার বাটনে ক্লিক করার আগে নিজেকে এই প্রশ্নগুলো করুন।</p><ul><li>এটি কোন সমস্যার সমাধান করবে তা কি পরিষ্কারভাবে ঠিক করেছি?</li><li>চলমান খরচসহ এটি কি আমার বাজেটের মধ্যে?</li><li>কয়েকটি বিস্তারিত ও সৎ রিভিউ কি পড়েছি?</li><li>আমার কাছে থাকা জিনিসের সঙ্গে এটি কি মানানসই?</li><li>ওয়ারেন্টি ও রিটার্ন পলিসি কি গ্রহণযোগ্য?</li><li>বাস্তবে কি আমি এটি যথেষ্ট ব্যবহার করব?</li></ul><p>প্রতিটি প্রশ্নের উত্তর যদি আত্মবিশ্বাসী "হ্যাঁ" হয়, তাহলে নিশ্চিন্তে আপনার নতুন কেনাকাটা উপভোগ করুন।</p>'],
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
