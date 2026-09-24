<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Product-review style bodies, one Bengali and one English version per
     * slot — the seeder picks per comment so the demo reads like real mixed-
     * language feedback.
     */
    private const REVIEWS = [
        ['হ্যাঁ, পণ্যটি দুর্দান্ত। ডেলিভারিও ঠিক সময়ে পেয়েছি।', 'The product is excellent and the delivery was on time.'],
        ['কোয়ালিটি খুব ভালো, অবশ্যই আবার কিনব।', 'Very good quality, I will definitely buy again.'],
        ['দাম অনুযায়ী সত্যিই ভালো একটা পণ্য। সবাইকে রিকমেন্ড করব।', 'Really good for the price. I would recommend it to everyone.'],
        ['প্রথমবার কিনলাম, কিন্তু বেশ সন্তুষ্ট। প্যাকেজিং সুন্দর ছিল।', 'First time buying but quite satisfied. The packaging was nice.'],
        ['বান্ধবীর জন্য কিনেছিলাম, সে অনেক পছন্দ করেছে।', 'Bought it for my friend and she loved it.'],
        ['কয়েকদিন ব্যবহারের পরে রিভিউ দিচ্ছি — কাজে দারুণ লাগছে।', 'Reviewing after using it for a few days — it works great.'],
        ['সাপোর্ট টিমও ছিল খুব হেল্পফুল। ধন্যবাদ।', 'The support team was very helpful too. Thank you.'],
    ];

    private const REPLIES = [
        'একদম ঠিক ধরেছেন। ধন্যবাদ!',
        'Great to hear, thank you!',
        'আমিও একই অভিজ্ঞতা পেয়েছি।',
        'I had the same experience too.',
    ];

    public function run(): void
    {
        $products = Product::query()
            ->active()
            ->whereHas('page', fn ($q) => $q->where('status', 'active'))
            ->inRandomOrder()
            ->take(6)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        $customers = $this->customers();

        foreach ($products as $index => $product) {
            $commentCount = $index % 2 === 0 ? 2 : 1;

            for ($i = 0; $i < $commentCount; $i++) {
                $user = $this->buyerOf($product, $customers) ?? $customers->random();

                [$bn, $en] = self::REVIEWS[rand(0, count(self::REVIEWS) - 1)];
                $createdAt = now()->subDays(rand(1, 25))->subHours(rand(0, 20));

                $comment = Comment::create([
                    'commentable_type' => Product::class,
                    'commentable_id' => $product->id,
                    'user_id' => $user->id,
                    'body' => rand(0, 1) === 0 ? $bn : $en,
                    'status' => 'approved',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if (rand(0, 100) <= 40) {
                    Comment::create([
                        'commentable_type' => Product::class,
                        'commentable_id' => $product->id,
                        'user_id' => $comment->user_id,
                        'parent_id' => $comment->id,
                        'body' => self::REPLIES[rand(0, count(self::REPLIES) - 1)],
                        'status' => 'approved',
                        'created_at' => $createdAt->addHours(rand(2, 48)),
                        'updated_at' => $createdAt->addHours(rand(2, 48)),
                    ]);
                }
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function customers(): Collection
    {
        $customers = User::role('customer')->get();

        if ($customers->isNotEmpty()) {
            return $customers;
        }

        return User::query()->orderBy('id')->limit(10)->get();
    }

    /**
     * A customer who actually ordered the product on a non-cancelled order —
     * the seeder keeps comments honest to the "only buyers can comment" rule.
     */
    private function buyerOf(Product $product, Collection $customers): ?User
    {
        return $customers->first(function (User $user) use ($product) {
            return Order::query()
                ->where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
        });
    }
}
