<?php

use App\Livewire\Admin\Services\Form as ServiceForm;
use App\Livewire\Admin\Types\Form as TypeForm;
use App\Livewire\Admin\Vouchers\Form as VoucherForm;
use App\Models\Language;
use App\Models\Type;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);

    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));
    $this->actingAs($admin);
});

dataset('slugged forms', [
    'type' => [TypeForm::class],
    'service' => [ServiceForm::class],
    'voucher' => [VoucherForm::class],
]);

it('fills the slug in as the name is typed', function (string $form) {
    Livewire::test($form)
        ->set('name.en', 'Hello World')
        ->assertSet('slug', 'hello-world')
        ->set('name.en', 'Hello World Again')
        ->assertSet('slug', 'hello-world-again');
})->with('slugged forms');

it('stops following the name once the admin types their own slug', function (string $form) {
    Livewire::test($form)
        ->set('name.en', 'Hello')
        ->set('slug', 'custom')
        ->set('name.en', 'Hello There')
        ->assertSet('slug', 'custom');
})->with('slugged forms');

it('keeps an existing slug when its name is edited', function () {
    $type = Type::create(['name' => ['en' => 'Gadgets'], 'slug' => 'gadgets', 'status' => 'active']);

    Livewire::test(TypeForm::class, ['id' => $type->id])
        ->set('name.en', 'Gadgets And More')
        ->assertSet('slug', 'gadgets');
});

it('cuts a long type name to the 30 characters the slug column holds', function () {
    $component = Livewire::test(TypeForm::class)
        ->set('name.en', 'An Extremely Long Type Name That Keeps On Going');

    expect(strlen($component->get('slug')))->toBeLessThanOrEqual(30)
        ->and($component->get('slug'))->not->toEndWith('-');

    $component->call('save')->assertHasNoErrors();
});

it('fills the slug in on the forms that already did', function (string $form, string $field) {
    Livewire::test($form)
        ->set("{$field}.en", 'Summer Sale')
        ->assertSet('slug', 'summer-sale');
})->with([
    'category' => [App\Livewire\Admin\Categories\Form::class, 'name'],
    'product' => [App\Livewire\Admin\Products\Form::class, 'name'],
    'post' => [App\Livewire\Admin\Posts\Form::class, 'title'],
    'page' => [App\Livewire\Admin\Pages\Form::class, 'title'],
]);
