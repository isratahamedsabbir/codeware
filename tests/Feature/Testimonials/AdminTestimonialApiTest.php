<?php

use App\Models\Product;
use App\Models\Testimonial;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->token = $this->admin->createToken('test')->plainTextToken;
});

it('lists testimonials', function () {
    Testimonial::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/testimonials', [
        'Authorization' => "Bearer {$this->token}",
    ])->assertOk()->assertJsonStructure([
        'data'  => [['id', 'name', 'comment']],
        'meta'  => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

it('shows a single testimonial', function () {
    $testimonial = Testimonial::factory()->create();

    $this->getJson("/api/v1/admin/testimonials/{$testimonial->id}", [
        'Authorization' => "Bearer {$this->token}",
    ])->assertOk()->assertJsonPath('data.id', $testimonial->id);
});

it('creates a testimonial', function () {
    $product = Product::factory()->create();

    $this->postJson('/api/v1/admin/testimonials', [
        'name'    => ['en' => 'John Doe', 'bn' => 'জন ডো'],
        'comment' => ['en' => 'Great product!', 'bn' => 'দারুণ পণ্য!'],
        'type'    => 'customer',
        'product_id' => $product->id,
    ], [
        'Authorization' => "Bearer {$this->token}",
    ])->assertCreated()->assertJsonStructure(['data' => ['id']]);

    expect(Testimonial::whereJsonContains('name->en', 'John Doe')->exists())->toBeTrue();
});

it('updates a testimonial', function () {
    $testimonial = Testimonial::factory()->create();

    $this->putJson("/api/v1/admin/testimonials/{$testimonial->id}", [
        'location' => 'Dhaka',
    ], [
        'Authorization' => "Bearer {$this->token}",
    ])->assertOk();

    expect($testimonial->fresh()->location)->toBe('Dhaka');
});

it('deletes a testimonial', function () {
    $testimonial = Testimonial::factory()->create();

    $this->deleteJson("/api/v1/admin/testimonials/{$testimonial->id}", [], [
        'Authorization' => "Bearer {$this->token}",
    ])->assertNoContent();

    expect(Testimonial::find($testimonial->id))->toBeNull();
    expect(Testimonial::withTrashed()->find($testimonial->id))->not->toBeNull();
});

it('requires authentication for admin endpoints', function () {
    $this->getJson('/api/v1/admin/testimonials')->assertUnauthorized();
});
