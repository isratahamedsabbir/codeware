<?php

use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CommentSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->users = User::factory()->count(5)->create()->each(
        fn (User $user) => $user->assignRole('customer')
    );

    $this->products = collect(range(1, 4))->map(fn () => Product::factory()->published()->create([
        'name' => ['en' => 'Product', 'bn' => ''],
    ]));

    $this->products->values()->each(function (Product $product, int $index) {
        pairPageFor($product, 'product', 'product-'.$index, $this->users->first()->id);
    });
});

it('seeds approved dummy comments on active products with a page', function () {
    $this->assertDatabaseCount('comments', 0);

    $this->seed(CommentSeeder::class);

    $comments = Comment::get();

    expect($comments)->not->toBeEmpty();

    foreach ($comments as $comment) {
        expect($comment->status)->toBe('approved');
        expect($comment->commentable_type)->toBe(Product::class);
        expect($comment->commentable_id)->toBeIn($this->products->pluck('id')->all());
        expect($comment->user->hasRole('customer'))->toBeTrue();
    }
});

it('keeps comments honest to the buyers-only rule when someone actually bought the product', function () {
    $top = $this->products->first();
    $buyer = $this->users->first();

    $order = Order::factory()->status('delivered')->create(['user_id' => $buyer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $top->id]);

    $this->seed(CommentSeeder::class);

    $topComments = Comment::where('commentable_id', $top->id)->whereNull('parent_id')->get();

    expect($topComments)->not->toBeEmpty();
});
