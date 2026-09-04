<?php

use App\Livewire\Admin\Pages\Form;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

it('switches constant value field to file picker when File is clicked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Form::class, ['id' => $page->id])
        ->call('addConstant')
        ->assertSet('constant.0.type', 'textarea')
        ->assertDontSee('mp-constant-0-value', false)
        ->call('setConstantType', 0, 'file')
        ->assertSet('constant.0.type', 'file')
        ->assertSee('mp-constant-0-value', false);
});

it('folds legacy single-line "text" constant into textarea on load', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create([
        'constant' => [['key' => 'title', 'type' => 'text', 'value' => 'Legacy value']],
    ]);

    $this->actingAs($admin);

    Livewire::test(Form::class, ['id' => $page->id])
        ->assertSet('constant.0.type', 'textarea')
        ->assertSet('constant.0.value', 'Legacy value');
});

it('writes constant back to the Page\'s constant column on save', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $page = Page::factory()->create();

    $this->actingAs($admin);

    Livewire::test(Form::class, ['id' => $page->id])
        ->call('addConstant')
        ->set('constant.0.key', 'og_type')
        ->set('constant.0.value', 'website')
        ->call('save');

    expect($page->fresh()->constant)->toBe([
        ['key' => 'og_type', 'type' => 'textarea', 'value' => 'website'],
    ]);
});
