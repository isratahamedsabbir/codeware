<?php

use App\Livewire\Admin\Posts\Form as PostForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin');
    $this->actingAs(User::factory()->create()->assignRole('admin'));
});

it('toggles the product Additional Data setting from the header event', function () {
    Setting::set('additional_data_products_enabled', '1');

    Livewire::test(ProductForm::class)->dispatch('toggle-additional-data');

    expect(Setting::productAdditionalDataEnabled())->toBeFalse();
});

it('toggles the post Additional Data setting from the header event', function () {
    Setting::set('additional_data_posts_enabled', '0');

    Livewire::test(PostForm::class)->dispatch('toggle-additional-data');

    expect(Setting::postAdditionalDataEnabled())->toBeTrue();
});
