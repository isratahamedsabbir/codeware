<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Demo blog comments — mixed Bengali/English bodies on published posts, with a
 * one-level-deep reply thread on a good share of them, plus a couple of
 * pending comments so the admin Comments screen has something to moderate.
 * Mirrors CommentSeeder's product reviews, but for the blog where any signed-
 * in user can take part (no purchase is involved).
 */
class BlogCommentSeeder extends Seeder
{
    private const BODIES = [
        ['চমৎকার লেখা, অনেক কিছু শিখলাম। ধন্যবাদ!', 'Excellent article, learned a lot. Thank you!'],
        ['এই বিষয়ে আরও বিস্তারিত লিখলে ভালো হতো।', 'I would love even more detail on this topic.'],
        ['রিডিং টাইম একদম সঠিক ছিল, দারুণ লেখা।', 'The reading time was spot on — great write-up.'],
        ['আমার টিমের সাথে শেয়ার করলাম। খুবই কাজের তথ্য।', 'Sharing this with my team, very useful info.'],
        ['একটু ভিন্নমত, তবে লেখাটি ভালো লেগেছে।', 'I have a slightly different take, but enjoyed it.'],
        ['দারুণ বিশ্লেষণ, এভাবে ভাবিনি আগে কখনো।', 'Great analysis, never thought of it that way.'],
        ['ব্যবহারিক কিছু টিপস পেলাম, ধন্যবাদ লেখককে।', 'Got some practical tips, thanks to the author.'],
        ['লেখার ধরন খুবই সাবলীল, পড়তে ভালো লাগলো।', 'Very readable, flowed nicely from start to end.'],
    ];

    private const REPLIES = [
        'একদম ঠিক বলেছেন। ধন্যবাদ!',
        'Good point, thanks for sharing!',
        'আমিও একই অভিজ্ঞতা পেয়েছি।',
        'Appreciate the feedback!',
        'বিস্তারিত তথ্যের জন্য কৃতজ্ঞতা।',
    ];

    public function run(): void
    {
        $posts = Post::query()
            ->published()
            ->whereHas('page', fn ($q) => $q->where('status', 'active'))
            ->inRandomOrder()
            ->take(6)
            ->get();

        if ($posts->isEmpty()) {
            return;
        }

        $customers = $this->customers();

        foreach ($posts as $index => $post) {
            // First posts carry a full thread, later ones fewer.
            $commentCount = $index < 2 ? 4 : ($index % 2 === 0 ? 3 : 2);

            for ($i = 0; $i < $commentCount; $i++) {
                $user = $customers->random();
                [$bn, $en] = self::BODIES[rand(0, count(self::BODIES) - 1)];
                $createdAt = now()->subDays(rand(1, 30))->subHours(rand(0, 20));

                $comment = Comment::create([
                    'commentable_type' => Post::class,
                    'commentable_id' => $post->id,
                    'user_id' => $user->id,
                    'body' => rand(0, 1) === 0 ? $bn : $en,
                    'status' => 'approved',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Anyone in the thread can reply — replies are one level deep.
                if (rand(0, 100) <= 45) {
                    $replyAt = $createdAt->addHours(rand(2, 48));

                    Comment::create([
                        'commentable_type' => Post::class,
                        'commentable_id' => $post->id,
                        'user_id' => $customers->random()->id,
                        'parent_id' => $comment->id,
                        'body' => self::REPLIES[rand(0, count(self::REPLIES) - 1)],
                        'status' => 'approved',
                        'created_at' => $replyAt,
                        'updated_at' => $replyAt,
                    ]);
                }
            }

            // A pending comment on the first two posts, so admin moderation
            // has something waiting out of the box.
            if ($index < 2) {
                $pendingAt = now()->subHours(rand(1, 8));

                Comment::create([
                    'commentable_type' => Post::class,
                    'commentable_id' => $post->id,
                    'user_id' => $customers->random()->id,
                    'body' => 'A freshly posted comment, awaiting moderation.',
                    'status' => 'pending',
                    'created_at' => $pendingAt,
                    'updated_at' => $pendingAt,
                ]);
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
}
