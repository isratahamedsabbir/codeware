<?php

use App\Livewire\Admin\Services\Index as ServicesIndex;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Builds a CSV the way the Services screen's export writes one. Rows are keyed
 * by header name so a test only spells out the columns it cares about.
 *
 * @param  list<array<string, string|int>>  $rows
 * @param  list<string>|null  $header
 */
function serviceImportCsv(array $rows, ?array $header = null): string
{
    $header ??= [
        'ID', 'Name (EN)', 'Name (BN)', 'Slug', 'Price', 'Status',
        'Featured Image', 'Sort Order', 'Created At',
        'Description (EN)', 'Description (BN)',
    ];

    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $header);

    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn (string $column) => (string) ($row[$column] ?? ''), $header));
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->import = fn (string $csv, bool $updateExisting = false) => Livewire::test(ServicesIndex::class)
        ->set('importFile', UploadedFile::fake()->createWithContent('services.csv', $csv))
        ->set('importUpdateExisting', $updateExisting)
        ->call('importCsv');
});

it('imports a new service with its translations, price, image, order and description', function () {
    $component = ($this->import)(serviceImportCsv([[
        'Name (EN)' => 'Home Installation',
        'Name (BN)' => 'হোম ইনস্টলেশন',
        'Slug' => 'home-installation',
        'Price' => '1500.50',
        'Status' => 'Active',
        'Featured Image' => 'https://example.test/installation.jpg',
        'Sort Order' => '4',
        'Description (EN)' => 'English description',
        'Description (BN)' => 'বাংলা বর্ণনা',
    ]]));

    $service = Service::sole();

    expect($component->get('importResult'))->toMatchArray([
        'created' => 1,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'warnings' => [],
    ])
        ->and($service->getTranslation('name', 'en'))->toBe('Home Installation')
        ->and($service->getTranslation('name', 'bn'))->toBe('হোম ইনস্টলেশন')
        ->and($service->slug)->toBe('home-installation')
        ->and((float) $service->price)->toBe(1500.5)
        ->and($service->status)->toBe('active')
        ->and($service->featured_image)->toBe('https://example.test/installation.jpg')
        ->and($service->sort_order)->toBe(4)
        ->and($service->created_by)->toBe($this->admin->id)
        ->and($service->getTranslation('description', 'en'))->toBe('English description')
        ->and($service->getTranslation('description', 'bn'))->toBe('বাংলা বর্ণনা');
});

it('creates an inactive service with a generated slug, zero price and the next sort order by default', function () {
    Service::factory()->create(['sort_order' => 7]);

    $component = ($this->import)(serviceImportCsv([[
        'Name (EN)' => 'A New Service',
        'Slug' => '',
        'Status' => '',
    ]]));

    $service = Service::whereJsonContains('name->en', 'A New Service')->sole();

    expect($component->get('importResult')['created'])->toBe(1)
        ->and($service->status)->toBe('inactive')
        ->and($service->slug)->toBe('a-new-service')
        ->and((float) $service->price)->toBe(0.0)
        ->and($service->sort_order)->toBe(8);
});

it('updates an existing service matched by id and keeps blank optional cells', function () {
    $service = Service::factory()->published()->create([
        'name' => ['en' => 'Old Service', 'bn' => 'পুরনো সেবা'],
        'slug' => 'old-service',
        'price' => 250,
        'featured_image' => 'https://example.test/old.jpg',
        'sort_order' => 3,
        'description' => ['en' => 'Keep this', 'bn' => ''],
    ]);

    $component = ($this->import)(
        serviceImportCsv([[
            'ID' => $service->id,
            'Name (EN)' => 'Renamed Service',
        ]], ['ID', 'Name (EN)']),
        true,
    );

    $service->refresh();

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 1, 'skipped' => 0])
        ->and($service->getTranslation('name', 'en'))->toBe('Renamed Service')
        ->and($service->slug)->toBe('old-service')
        ->and((float) $service->price)->toBe(250.0)
        ->and($service->status)->toBe('active')
        ->and($service->featured_image)->toBe('https://example.test/old.jpg')
        ->and($service->sort_order)->toBe(3)
        ->and($service->getTranslation('description', 'en'))->toBe('Keep this');
});

it('finds an existing service by slug when the id column is blank', function () {
    $service = Service::factory()->create([
        'name' => ['en' => 'Legacy Service', 'bn' => ''],
        'slug' => 'legacy-service',
    ]);

    $component = ($this->import)(serviceImportCsv([[
        'Slug' => 'legacy-service',
        'Name (EN)' => 'Legacy Service Renamed',
        'Status' => 'Inactive',
    ]]), true);

    $service->refresh();

    expect($component->get('importResult')['updated'])->toBe(1)
        ->and($service->getTranslation('name', 'en'))->toBe('Legacy Service Renamed')
        ->and($service->status)->toBe('inactive');
});

it('skips rows for existing services when the update toggle is off', function () {
    $service = Service::factory()->create([
        'name' => ['en' => 'Keep Me', 'bn' => ''],
        'slug' => 'keep-me',
    ]);

    $component = ($this->import)(serviceImportCsv([[
        'ID' => $service->id,
        'Name (EN)' => 'Should Not Replace',
    ]]));

    expect($component->get('importResult'))->toMatchArray(['created' => 0, 'updated' => 0, 'skipped' => 1])
        ->and($service->refresh()->getTranslation('name', 'en'))->toBe('Keep Me');
});

it('reports a row-level error but still imports the other rows', function () {
    $component = ($this->import)(serviceImportCsv([
        ['Name (EN)' => '', 'Slug' => 'orphan-row'],
        ['Name (EN)' => 'Valid Service', 'Slug' => 'valid-service'],
    ]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0])->toContain('Line 2: Name (EN) is required.')
        ->and(Service::whereJsonContains('name->en', 'Valid Service')->exists())->toBeTrue();
});

it('rejects a non-numeric price as a row error', function () {
    $component = ($this->import)(serviceImportCsv([[
        'Name (EN)' => 'Broken Price',
        'Price' => 'not-a-number',
    ]]));

    $result = $component->get('importResult');

    expect($result['created'])->toBe(0)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'][0])->toContain('"price" must be a number')
        ->and(Service::count())->toBe(0);
});

it('appends a suffix when an imported slug is already taken by a trashed service', function () {
    Service::factory()->create(['slug' => 'consulting'])->delete();

    ($this->import)(serviceImportCsv([[
        'Name (EN)' => 'Another Consulting',
        'Slug' => 'consulting',
    ]]));

    expect(Service::whereJsonContains('name->en', 'Another Consulting')->sole()->slug)->toBe('consulting-2');
});

it('accepts a header with a UTF-8 BOM and different casing', function () {
    $csv = "\xEF\xBB\xBFname (en),name (bn)\nBom Service,বম সেবা\n";

    $component = ($this->import)($csv);

    expect($component->get('importResult')['created'])->toBe(1)
        ->and(Service::sole()->getTranslation('name', 'en'))->toBe('Bom Service')
        ->and(Service::sole()->getTranslation('name', 'bn'))->toBe('বম সেবা');
});

it('requires a CSV file before importing', function () {
    Livewire::test(ServicesIndex::class)
        ->call('importCsv')
        ->assertHasErrors(['importFile']);
});

it('renders the import form only while its modal is open', function () {
    $component = Livewire::test(ServicesIndex::class)
        ->assertSet('importModalOpen', false)
        ->assertDontSee('Overwrite existing services')
        ->call('openImport')
        ->assertSet('importModalOpen', true)
        ->assertSee('Overwrite existing services')
        ->assertDispatched('open-modal', name: 'service-import')
        ->call('closeImport')
        ->assertSet('importModalOpen', false)
        ->assertDispatched('close-modal', name: 'service-import');
});
