<?php

use App\Livewire\Admin\Pages\Form;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

it('switches content value field to file picker when File is clicked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Form::class, ['id' => $page->id])
        ->call('addContent')
        ->assertSet('content.0.type', 'textarea')
        ->assertDontSee('mp-content-0-value', false)
        ->call('setContentType', 0, 'file')
        ->assertSet('content.0.type', 'file')
        ->assertSee('mp-content-0-value', false);
});

it('folds legacy single-line "text" metadata into textarea on load', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create([
        'metadata' => [['key' => 'title', 'type' => 'text', 'value' => 'Legacy value']],
    ]);

    $this->actingAs($admin);

    Livewire::test(Form::class, ['id' => $page->id])
        ->assertSet('content.0.type', 'textarea')
        ->assertSet('content.0.value', 'Legacy value');
});

it('still writes content back to the Page\'s metadata column on save', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Form::class, ['id' => $page->id])
        ->call('addContent')
        ->set('content.0.key', 'og_type')
        ->set('content.0.value', 'website')
        ->call('save');

    expect($page->fresh()->metadata)->toBe([
        ['key' => 'og_type', 'type' => 'textarea', 'value' => 'website'],
    ]);
});
