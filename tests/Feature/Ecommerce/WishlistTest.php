<?php

use App\Livewire\Frontend\WishlistButton;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Favorites;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

function wishlistProduct(string $slug, array $attributes = []): Product
{
    $product = Product::factory()->published()->create($attributes + ['sort_order' => 0]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

it('lets a guest favorite a product from its card', function () {
    $product = wishlistProduct('favorite-me', ['name' => ['en' => 'Favoritable Item', 'bn' => '']]);

    Livewire::test(WishlistButton::class, ['productId' => $product->id])
        ->call('toggle')
        ->assertSet('isFavorited', true);

    expect(session('wishlist'))->toBe([$product->id]);
});

it('shows favorited products on the favorites page and hides removed ones', function () {
    $product = wishlistProduct('saved-item', ['name' => ['en' => 'Saved Item', 'bn' => '']]);

    Livewire::test(WishlistButton::class, ['productId' => $product->id])->call('toggle');

    get('/favorites')
        ->assertOk()
        ->assertSee('Saved Item')
        ->assertSee(route('products.show', 'saved-item'));

    Livewire::test(WishlistButton::class, ['productId' => $product->id])->call('toggle');

    get('/favorites')
        ->assertOk()
        ->assertDontSee('Saved Item')
        ->assertSee('No favorites yet');
});

it('stores favorites as wishlist rows once a user is signed in', function () {
    $user = User::factory()->create();
    $product = wishlistProduct('account-item', ['name' => ['en' => 'Account Item', 'bn' => '']]);

    actingAs($user);

    Livewire::test(WishlistButton::class, ['productId' => $product->id])->call('toggle');

    $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

    Livewire::test(WishlistButton::class, ['productId' => $product->id])->call('toggle');

    $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
});

it('merges guest session favorites into the user wishlist on sign in', function () {
    $user = User::factory()->create();
    $product = wishlistProduct('merge-me', ['name' => ['en' => 'Merge Item', 'bn' => '']]);

    Livewire::test(WishlistButton::class, ['productId' => $product->id])->call('toggle');
    expect(session('wishlist'))->toBe([$product->id]);

    actingAs($user);
    Favorites::mergeSessionIntoDatabase();

    $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    expect(session()->has('wishlist'))->toBeFalse();
});

it('renders a favorite toggle on the product card', function () {
    wishlistProduct('card-item', ['name' => ['en' => 'Card Favorite Item', 'bn' => '']]);

    get('/shop')
        ->assertOk()
        ->assertSee('Add to favorites');
});

it('renders the favorites count in the storefront header', function () {
    $user = User::factory()->create();
    $product = wishlistProduct('counted-item', ['name' => ['en' => 'Counted Item', 'bn' => '']]);

    actingAs($user);
    Livewire::test(WishlistButton::class, ['productId' => $product->id])->call('toggle');

    get('/')
        ->assertOk()
        ->assertSee('My favorites');
});
