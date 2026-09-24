<?php

use App\Livewire\Frontend\ProductReviews;
use App\Models\Feature;
use App\Models\Language;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);

    $this->product = Product::factory()->published()->create(['name' => ['en' => 'Spearmint Tea', 'bn' => ''], 'sort_order' => 0]);
    pairPageFor($this->product, 'product', 'spearmint-tea', User::factory()->create()->id);
    $this->customer = User::factory()->create(['email' => 'buyer@example.com']);
});

/** An order for $this->product, attached to the customer (or placed as a guest with their email). */
function orderProductFor(User $user, string $status = 'pending', bool $asGuest = false): Order
{
    $order = Order::factory()->create([
        'user_id' => $asGuest ? null : $user->id,
        'customer_email' => $user->email,
        'status' => $status,
    ]);
    $order->items()->create([
        'type' => 'product', 'product_id' => test()->product->id, 'item_name' => 'Spearmint Tea',
        'unit_price' => 450, 'quantity' => 1, 'line_total' => 450,
    ]);

    return $order;
}

it('asks guests to sign in and hides the form', function () {
    get('/products/spearmint-tea')->assertOk()
        ->assertSee('Customer reviews')
        ->assertSee('Sign in to write a review')
        ->assertDontSee('Submit review');
});

it('does not let a customer who never bought the product review it', function () {
    actingAs($this->customer);

    Livewire::test(ProductReviews::class, ['productId' => $this->product->id])
        ->assertSee('Only customers who bought this product can review it.')
        ->assertDontSee('Submit review')
        ->set('rating', 5)->set('body', 'Trying to sneak a review in.')
        ->call('submit')
        ->assertHasErrors('body');

    expect(Review::count())->toBe(0);
});

it('lets a buyer submit one review, which waits for approval', function () {
    orderProductFor($this->customer);
    actingAs($this->customer);

    Livewire::test(ProductReviews::class, ['productId' => $this->product->id])
        ->assertSee('Submit review')
        ->set('rating', 4)
        ->set('title', 'Lovely flavour')
        ->set('body', 'Fresh and aromatic, great with honey.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('awaiting approval')
        ->assertDontSee('Submit review');

    $review = Review::sole();
    expect($review->status)->toBe('pending')
        ->and($review->rating)->toBe(4)
        ->and($review->user_id)->toBe($this->customer->id)
        ->and($review->reviewable->is($this->product))->toBeTrue();

    // A second review from the same customer is refused.
    Livewire::test(ProductReviews::class, ['productId' => $this->product->id])
        ->set('rating', 1)->set('body', 'Changed my mind entirely.')
        ->call('submit')
        ->assertHasErrors('body');

    expect(Review::count())->toBe(1);
});

it('counts guest orders placed with the customer email, but not cancelled ones', function () {
    orderProductFor($this->customer, 'cancelled');
    expect($this->product->wasPurchasedBy($this->customer))->toBeFalse();

    orderProductFor($this->customer, 'delivered', asGuest: true);
    expect($this->product->fresh()->wasPurchasedBy($this->customer))->toBeTrue();
});

it('requires a star rating and a real review', function () {
    orderProductFor($this->customer);
    actingAs($this->customer);

    Livewire::test(ProductReviews::class, ['productId' => $this->product->id])
        ->set('body', 'short')
        ->call('submit')
        ->assertHasErrors(['rating', 'body']);
});

it('shows only approved reviews with the rating summary and a verified-purchase badge', function () {
    orderProductFor($this->customer);
    $this->product->reviews()->create(['user_id' => $this->customer->id, 'rating' => 5, 'title' => 'Best tea', 'body' => 'Absolutely love it.', 'status' => 'approved']);
    $this->product->reviews()->create(['user_id' => User::factory()->create()->id, 'rating' => 1, 'body' => 'Hidden pending review.', 'status' => 'pending']);

    get('/products/spearmint-tea')->assertOk()
        ->assertSee('Best tea')
        ->assertSee('Verified purchase')
        ->assertSee('Based on 1 review')
        ->assertDontSee('Based on 1 reviews')
        ->assertDontSee('Hidden pending review.');
});

it('hides the reviews section when the reviews feature is off', function () {
    Feature::create(['key' => 'reviews', 'label' => 'Reviews', 'is_enabled' => false]);

    get('/products/spearmint-tea')->assertOk()->assertDontSee('Customer reviews');
});
