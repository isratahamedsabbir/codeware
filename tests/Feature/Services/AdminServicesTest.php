<?php

use App\Livewire\Admin\Services\Form as ServiceForm;
use App\Livewire\Admin\Services\Index as ServiceIndex;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('guests and non-admins are blocked from the services screen', function () {
    auth()->logout();
    $this->get(route('admin.services'))->assertRedirect('/login');

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.services'))->assertForbidden();
});

it('renders the services index with existing services', function () {
    Service::factory()->create(['name' => ['en' => 'Consulting', 'bn' => '']]);

    Livewire::test(ServiceIndex::class)
        ->assertOk()
        ->assertSee('Consulting');
});

it('filters services by search', function () {
    Service::factory()->create(['name' => ['en' => 'Consulting', 'bn' => '']]);
    Service::factory()->create(['name' => ['en' => 'Installation', 'bn' => '']]);

    Livewire::test(ServiceIndex::class)
        ->set('search', 'Consulting')
        ->assertSee('Consulting')
        ->assertDontSee('Installation');
});

it('creates a service, inactive by default, with an auto-generated slug', function () {
    Livewire::test(ServiceForm::class)
        ->set('name.en', 'Home Installation')
        ->set('price', '1500')
        ->call('save');

    $service = Service::sole();
    expect($service->getTranslation('name', 'en', false))->toBe('Home Installation')
        ->and($service->slug)->toBe('home-installation')
        ->and((float) $service->price)->toBe(1500.0)
        ->and($service->status)->toBe('inactive');
});

it('rejects a duplicate slug', function () {
    Service::factory()->create(['slug' => 'consulting']);

    Livewire::test(ServiceForm::class)
        ->set('name.en', 'Consulting')
        ->set('slug', 'consulting')
        ->set('price', '100')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('updates an existing service', function () {
    $service = Service::factory()->create(['name' => ['en' => 'Consulting', 'bn' => ''], 'status' => 'active']);

    Livewire::test(ServiceForm::class, ['id' => $service->id])
        ->assertSet('name.en', 'Consulting')
        ->set('name.en', 'Premium Consulting')
        ->set('price', '2000')
        ->call('save');

    expect($service->fresh()->getTranslation('name', 'en', false))->toBe('Premium Consulting')
        ->and((float) $service->fresh()->price)->toBe(2000.0);
});

it('does not reset an inactive service back to active when saved from the form', function () {
    $service = Service::factory()->draft()->create(['name' => ['en' => 'Consulting', 'bn' => '']]);

    Livewire::test(ServiceForm::class, ['id' => $service->id])
        ->set('name.en', 'Consulting Plus')
        ->set('price', '500')
        ->call('save');

    expect($service->fresh()->status)->toBe('inactive');
});

it('toggles a service\'s status from the index', function () {
    $service = Service::factory()->published()->create();

    Livewire::test(ServiceIndex::class)
        ->call('toggleStatus', $service->id);

    expect($service->refresh()->status)->toBe('inactive');
});

it('deletes a service', function () {
    $service = Service::factory()->create();

    Livewire::test(ServiceIndex::class)
        ->call('confirmDelete', $service->id)
        ->call('delete');

    expect(Service::find($service->id))->toBeNull();
});
