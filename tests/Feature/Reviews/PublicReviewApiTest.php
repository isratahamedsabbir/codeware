<?php

use App\Models\Feature;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists only approved reviews for a post, newest first', function () {
    $post = Post::factory()->published()->create();

    Review::factory()->pending()->for($post, 'reviewable')->create(['title' => 'Pending review', 'rating' => 5]);
    Review::factory()->rejected()->for($post, 'reviewable')->create(['title' => 'Rejected review', 'rating' => 2]);
    Review::factory()->approved()->for($post, 'reviewable')->create(['title' => 'Approved review', 'rating' => 4]);

    $response = $this->getJson("/api/v1/reviews?reviewable_type=post&reviewable_id={$post->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Approved review')
        ->assertJsonPath('data.0.rating', 4);
});

it('lists reviews for a product and a service the same way', function () {
    $product = Product::factory()->published()->create();
    $service = Service::factory()->published()->create();

    Review::factory()->approved()->for($product, 'reviewable')->create(['title' => 'On the product', 'rating' => 5]);
    Review::factory()->approved()->for($service, 'reviewable')->create(['title' => 'On the service', 'rating' => 3]);

    $this->getJson("/api/v1/reviews?reviewable_type=product&reviewable_id={$product->id}")
        ->assertOk()->assertJsonPath('data.0.title', 'On the product');

    $this->getJson("/api/v1/reviews?reviewable_type=service&reviewable_id={$service->id}")
        ->assertOk()->assertJsonPath('data.0.title', 'On the service');
});

it('rejects an unknown reviewable_type', function () {
    $this->getJson('/api/v1/reviews?reviewable_type=order&reviewable_id=1')
        ->assertJsonValidationErrors(['reviewable_type']);
});

it('requires a logged-in user to post a review', function () {
    $post = Post::factory()->published()->create();

    $this->postJson('/api/v1/reviews', [
        'reviewable_type' => 'post',
        'reviewable_id' => $post->id,
        'rating' => 5,
        'body' => 'Great',
    ])->assertUnauthorized();
});

it('creates a pending review for a logged-in user', function () {
    $post = Post::factory()->published()->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reviews', [
        'reviewable_type' => 'post',
        'reviewable_id' => $post->id,
        'rating' => 5,
        'title' => 'Excellent',
        'body' => 'Highly recommended',
    ]);

    $response->assertCreated();

    $review = Review::sole();
    expect($review->status)->toBe('pending')
        ->and($review->rating)->toBe(5)
        ->and($review->title)->toBe('Excellent')
        ->and($review->user_id)->toBe($user->id)
        ->and($review->reviewable_type)->toBe(Post::class)
        ->and($review->reviewable_id)->toBe($post->id);
});

it('allows a review without a title', function () {
    $post = Post::factory()->published()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/reviews', [
        'reviewable_type' => 'post',
        'reviewable_id' => $post->id,
        'rating' => 3,
        'body' => 'Just a body',
    ])->assertCreated();
});

it('rejects a rating outside the 1-5 range', function () {
    $post = Post::factory()->published()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/reviews', [
        'reviewable_type' => 'post',
        'reviewable_id' => $post->id,
        'rating' => 9,
        'body' => 'Invalid rating',
    ])->assertJsonValidationErrors(['rating']);
});

it('rejects reviewing a draft post', function () {
    $post = Post::factory()->draft()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/reviews', [
        'reviewable_type' => 'post',
        'reviewable_id' => $post->id,
        'rating' => 4,
        'body' => 'Hello',
    ])->assertJsonValidationErrors(['reviewable_id']);
});

it('lets a user delete their own review, but not someone else\'s', function () {
    $post = Post::factory()->published()->create();
    $owner = User::factory()->create();
    $otherReview = Review::factory()->approved()->for($post, 'reviewable')->create();

    Sanctum::actingAs($owner);
    $own = Review::factory()->approved()->for($post, 'reviewable')->create(['user_id' => $owner->id]);

    $this->deleteJson("/api/v1/reviews/{$own->id}")->assertOk();
    expect(Review::find($own->id))->toBeNull();

    $this->deleteJson("/api/v1/reviews/{$otherReview->id}")->assertNotFound();
    expect(Review::find($otherReview->id))->not->toBeNull();
});

it('is blocked when the reviews feature is disabled', function () {
    Feature::create(['key' => 'reviews', 'label' => 'Reviews', 'is_enabled' => false]);

    $this->getJson('/api/v1/reviews?reviewable_type=post&reviewable_id=1')->assertNotFound();
});
