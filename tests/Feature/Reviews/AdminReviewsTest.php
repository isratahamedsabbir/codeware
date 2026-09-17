<?php

use App\Livewire\Admin\Reviews\Index as ReviewsIndex;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('guests and staff are blocked from the reviews screen', function () {
    auth()->logout();
    $this->get(route('admin.reviews'))->assertRedirect('/login');

    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->get(route('admin.reviews'))->assertForbidden();
});

it('renders the reviews index with existing reviews', function () {
    $post = Post::factory()->published()->create();
    $review = Review::factory()->approved()->for($post, 'reviewable')->create(['title' => 'Loved it']);

    Livewire::test(ReviewsIndex::class)
        ->assertSee('Loved it')
        ->assertSee($review->rating);
});

it('filters reviews by status', function () {
    $post = Post::factory()->published()->create();
    Review::factory()->approved()->for($post, 'reviewable')->create(['title' => 'Approved one']);
    Review::factory()->pending()->for($post, 'reviewable')->create(['title' => 'Pending one']);

    Livewire::test(ReviewsIndex::class)
        ->set('statusFilter', 'approved')
        ->assertSee('Approved one')
        ->assertDontSee('Pending one');
});

it('filters reviews by reviewable type', function () {
    $post = Post::factory()->published()->create();
    $product = Product::factory()->published()->create();
    Review::factory()->for($post, 'reviewable')->create(['title' => 'About the post']);
    Review::factory()->for($product, 'reviewable')->create(['title' => 'About the product']);

    Livewire::test(ReviewsIndex::class)
        ->set('typeFilter', 'product')
        ->assertSee('About the product')
        ->assertDontSee('About the post');
});

it('filters reviews by rating', function () {
    $post = Post::factory()->published()->create();
    Review::factory()->for($post, 'reviewable')->create(['rating' => 5, 'title' => 'Five star']);
    Review::factory()->for($post, 'reviewable')->create(['rating' => 1, 'title' => 'One star']);

    Livewire::test(ReviewsIndex::class)
        ->set('ratingFilter', '5')
        ->assertSee('Five star')
        ->assertDontSee('One star');
});

it('searches reviews by title, body or author', function () {
    $post = Post::factory()->published()->create();
    Review::factory()->for($post, 'reviewable')->create(['title' => 'superb product', 'body' => 'Works great']);
    Review::factory()->for($post, 'reviewable')->create(['title' => 'Meh', 'body' => 'superb quality']);

    Livewire::test(ReviewsIndex::class)
        ->set('search', 'superb')
        ->assertSee('superb product')
        ->assertSee('superb quality');
});

it('approves a pending review from the index', function () {
    $post = Post::factory()->published()->create();
    $review = Review::factory()->pending()->for($post, 'reviewable')->create();

    Livewire::test(ReviewsIndex::class)
        ->call('updateStatus', $review->id, 'approved');

    expect($review->fresh()->status)->toBe('approved');
});

it('rejects a review from the index', function () {
    $post = Post::factory()->published()->create();
    $review = Review::factory()->pending()->for($post, 'reviewable')->create();

    Livewire::test(ReviewsIndex::class)
        ->call('updateStatus', $review->id, 'rejected');

    expect($review->fresh()->status)->toBe('rejected');
});

it('deletes a review from the index', function () {
    $post = Post::factory()->published()->create();
    $review = Review::factory()->for($post, 'reviewable')->create();

    Livewire::test(ReviewsIndex::class)
        ->call('confirmDelete', $review->id)
        ->call('delete');

    expect(Review::find($review->id))->toBeNull();
});

it('bulk deletes selected reviews', function () {
    $post = Post::factory()->published()->create();
    $reviews = Review::factory()->for($post, 'reviewable')->count(3)->create();

    Livewire::test(ReviewsIndex::class)
        ->call('toggleSelect', $reviews[0]->id)
        ->call('toggleSelect', $reviews[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 reviews deleted successfully');

    expect(Review::find($reviews[0]->id))->toBeNull()
        ->and(Review::find($reviews[1]->id))->toBeNull()
        ->and(Review::find($reviews[2]->id))->not->toBeNull();
});
