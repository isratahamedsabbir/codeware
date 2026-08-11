<?php

use App\Models\Testimonial;

it('lists published testimonials', function () {
    Testimonial::factory()->count(3)->published()->create();
    Testimonial::factory()->create(['status' => 'draft']);

    $this->getJson('/api/v1/testimonials')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('shows a single testimonial', function () {
    $testimonial = Testimonial::factory()->published()->create();

    $this->getJson("/api/v1/testimonials/{$testimonial->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $testimonial->id);
});

it('filters testimonials by type', function () {
    Testimonial::factory()->published()->count(2)->create(['type' => 'customer']);
    Testimonial::factory()->published()->create(['type' => 'partner']);

    $this->getJson('/api/v1/testimonials?type=customer')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('respects locale parameter', function () {
    Testimonial::factory()->published()->create([
        'name' => ['en' => 'John', 'bn' => 'জন'],
    ]);

    $this->getJson('/api/v1/testimonials?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'জন');
});
