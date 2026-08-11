# Dealer Locator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a dealer locator — migration, model, Livewire admin CRUD, and public REST API with district/upazila/category filtering and Haversine proximity search.

**Architecture:** Single `dealers` table with a bare `dealer_product_categories` pivot to the existing `product_categories` table. Livewire admin follows the same pattern as `Admin\Products\Index`. Public API uses a bounding-box DB pre-filter then PHP Haversine for exact distance — this avoids raw SQL trig functions that SQLite (used in tests) does not support.

**Tech Stack:** Laravel 11, Livewire 3, Flux UI, Spatie Translatable (EN/BN), Pest PHP, PHP 8.5 at `C:\Users\User\.config\herd\bin\php85\php.exe`.

---

## File Map

**Create:**
- `database/migrations/[ts]_create_dealers_table.php`
- `database/migrations/[ts]_create_dealer_product_categories_table.php`
- `app/Models/Dealer.php`
- `database/factories/DealerFactory.php`
- `app/Livewire/Admin/Dealers/Index.php`
- `resources/views/livewire/admin/dealers/index.blade.php`
- `app/Http/Controllers/Api/V1/DealerController.php`
- `tests/Feature/Dealers/PublicDealerApiTest.php`

**Modify:**
- `routes/admin.php` — add dealers route
- `routes/api.php` — add public dealer routes + import
- `resources/views/layouts/admin.blade.php` — add Dealer Network nav group

---

## Task 1: Migrations

**Files:**
- Create: `database/migrations/[ts]_create_dealers_table.php`
- Create: `database/migrations/[ts]_create_dealer_product_categories_table.php`

- [ ] **Step 1: Generate migration files**

```bash
php artisan make:migration create_dealers_table
php artisan make:migration create_dealer_product_categories_table
```

- [ ] **Step 2: Write the dealers migration**

Open `create_dealers_table` and replace `up()`/`down()`:

```php
public function up(): void
{
    Schema::create('dealers', function (Blueprint $table) {
        $table->id();
        $table->json('name');
        $table->string('slug')->unique();
        $table->json('address')->nullable();
        $table->string('district');
        $table->string('upazila')->nullable();
        $table->string('phone');
        $table->string('email')->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->softDeletes();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('dealers');
}
```

- [ ] **Step 3: Write the pivot migration**

Open `create_dealer_product_categories_table` and replace `up()`/`down()`:

```php
public function up(): void
{
    Schema::create('dealer_product_categories', function (Blueprint $table) {
        $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
        $table->primary(['dealer_id', 'product_category_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('dealer_product_categories');
}
```

- [ ] **Step 4: Run migrations**

```bash
php artisan migrate
```

Expected: two "Migrating" lines, both DONE.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: add dealers and dealer_product_categories migrations"
```

---

## Task 2: Model and Factory

**Files:**
- Create: `app/Models/Dealer.php`
- Create: `database/factories/DealerFactory.php`

- [ ] **Step 1: Create Dealer model**

Create `app/Models/Dealer.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Dealer extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name', 'address'];

    protected $fillable = [
        'name', 'slug', 'address', 'district', 'upazila',
        'phone', 'email', 'latitude', 'longitude',
        'status', 'sort_order',
    ];

    protected $casts = [
        'latitude'   => 'decimal:7',
        'longitude'  => 'decimal:7',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Dealer $dealer) {
            if (empty($dealer->slug)) {
                $name = is_array($dealer->name)
                    ? ($dealer->name['en'] ?? reset($dealer->name))
                    : $dealer->name;
                $dealer->slug = Str::slug($name);
            }
        });
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'dealer_product_categories'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
```

- [ ] **Step 2: Create DealerFactory**

Create `database/factories/DealerFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DealerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();
        return [
            'name'       => ['en' => $name, 'bn' => $name],
            'address'    => ['en' => fake()->address(), 'bn' => fake()->address()],
            'district'   => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna']),
            'upazila'    => fake()->city(),
            'phone'      => '+880' . fake()->numerify('17########'),
            'email'      => fake()->optional()->safeEmail(),
            'latitude'   => fake()->latitude(20.5, 26.6),
            'longitude'  => fake()->longitude(88.0, 92.7),
            'status'     => 'active',
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
```

Note: No `slug` field — the model's `booted()` auto-generates it from `name.en`. This ensures `Dealer::factory()->create(['name' => ['en' => 'Custom Name', 'bn' => '']])` gets slug `custom-name`, not the factory's fake company slug.

- [ ] **Step 3: Commit**

```bash
git add app/Models/Dealer.php database/factories/DealerFactory.php
git commit -m "feat: add Dealer model and factory"
```

---

## Task 3: Admin Navigation and Route

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `routes/admin.php`

- [ ] **Step 1: Add Dealer Network nav group to admin sidebar**

In `resources/views/layouts/admin.blade.php`, add a new navlist group after the existing "Products" group, just before the closing `</flux:navlist>`:

```blade
<flux:navlist.group heading="Dealer Network" class="mt-2">
    <flux:navlist.item
        icon="map-pin"
        href="{{ route('admin.dealers') }}"
        :current="request()->routeIs('admin.dealers')"
        wire:navigate
    >
        Dealers
    </flux:navlist.item>
</flux:navlist.group>
```

- [ ] **Step 2: Add dealers route**

In `routes/admin.php`, append:

```php
Route::get('/dealers', \App\Livewire\Admin\Dealers\Index::class)->name('dealers');
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/admin.blade.php routes/admin.php
git commit -m "feat: add Dealer Network nav and admin route"
```

---

## Task 4: Dealers Livewire Component

**Files:**
- Create: `app/Livewire/Admin/Dealers/Index.php`
- Create: `resources/views/livewire/admin/dealers/index.blade.php`

- [ ] **Step 1: Create Livewire component**

Create `app/Livewire/Admin/Dealers/Index.php`:

```php
<?php

namespace App\Livewire\Admin\Dealers;

use App\Models\Dealer;
use App\Models\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    #[Validate('required|string|max:255')]
    public string $name_en = '';
    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';
    #[Validate('nullable|string|max:255')]
    public string $slug = '';
    #[Validate('nullable|string')]
    public string $address_en = '';
    #[Validate('nullable|string')]
    public string $address_bn = '';
    #[Validate('required|string|max:100')]
    public string $district = '';
    #[Validate('nullable|string|max:100')]
    public string $upazila = '';
    #[Validate('required|string|max:50')]
    public string $phone = '';
    #[Validate('nullable|email|max:255')]
    public string $email = '';
    #[Validate('nullable|numeric|between:-90,90')]
    public ?string $latitude = null;
    #[Validate('nullable|numeric|between:-180,180')]
    public ?string $longitude = null;
    #[Validate('in:active,inactive')]
    public string $status = 'active';
    public int $sort_order = 0;
    public array $selectedCategories = [];

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    #[Computed]
    public function productCategories()
    {
        return ProductCategory::orderBy('sort_order')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'dealer-form');
    }

    public function openEdit(int $id): void
    {
        $dealer = Dealer::findOrFail($id);
        $this->editingId   = $id;
        $this->name_en     = $dealer->getTranslation('name', 'en', false) ?? '';
        $this->name_bn     = $dealer->getTranslation('name', 'bn', false) ?? '';
        $this->slug        = $dealer->slug;
        $this->address_en  = $dealer->getTranslation('address', 'en', false) ?? '';
        $this->address_bn  = $dealer->getTranslation('address', 'bn', false) ?? '';
        $this->district    = $dealer->district;
        $this->upazila     = $dealer->upazila ?? '';
        $this->phone       = $dealer->phone;
        $this->email       = $dealer->email ?? '';
        $this->latitude    = $dealer->latitude !== null ? (string) $dealer->latitude : null;
        $this->longitude   = $dealer->longitude !== null ? (string) $dealer->longitude : null;
        $this->status      = $dealer->status;
        $this->sort_order  = $dealer->sort_order;
        $this->selectedCategories = $dealer->categories->pluck('id')->toArray();
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'dealer-form');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'      => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'slug'      => $this->slug,
            'address'   => array_filter(['en' => $this->address_en, 'bn' => $this->address_bn]) ?: null,
            'district'  => $this->district,
            'upazila'   => $this->upazila ?: null,
            'phone'     => $this->phone,
            'email'     => $this->email ?: null,
            'latitude'  => $this->latitude !== null && $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null && $this->longitude !== '' ? (float) $this->longitude : null,
            'status'    => $this->status,
            'sort_order'=> $this->sort_order,
        ];

        if ($this->editingId) {
            $dealer = Dealer::findOrFail($this->editingId);
            $dealer->update($data);
        } else {
            $dealer = Dealer::create($data);
        }

        $dealer->categories()->sync($this->selectedCategories);

        $this->resetForm();
        $this->dispatch('close-modal', name: 'dealer-form');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'dealer-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Dealer::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'dealer-delete');
    }

    private function resetForm(): void
    {
        $this->reset(
            'name_en', 'name_bn', 'slug', 'address_en', 'address_bn',
            'district', 'upazila', 'phone', 'email',
            'latitude', 'longitude', 'status', 'sort_order',
            'selectedCategories', 'editingId'
        );
        $this->status = 'active';
    }

    public function render()
    {
        return view('livewire.admin.dealers.index', [
            'dealers' => Dealer::query()
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhere('district', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('categories')
                ->orderBy('sort_order')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Dealers']);
    }
}
```

- [ ] **Step 2: Create Blade view**

Create `resources/views/livewire/admin/dealers/index.blade.php`:

```blade
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Dealers</flux:heading>
            <flux:subheading>Manage Bangladesh dealer network.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">
            New Dealer
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search dealers…" icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>District</flux:table.column>
            <flux:table.column>Upazila</flux:table.column>
            <flux:table.column>Categories</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($dealers as $dealer)
                <flux:table.row :key="$dealer->id">
                    <flux:table.cell>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $dealer->getTranslation('name', 'en', false) }}</p>
                            @if ($dealer->getTranslation('name', 'bn', false))
                                <p class="text-xs text-zinc-500">{{ $dealer->getTranslation('name', 'bn', false) }}</p>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $dealer->district }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $dealer->upazila ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="zinc">{{ $dealer->categories_count }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $dealer->status === 'active' ? 'green' : 'zinc' }}">
                            {{ ucfirst($dealer->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-end">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEdit({{ $dealer->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:click="confirmDelete({{ $dealer->id }})">Delete</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-400 py-8">No dealers found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $dealers->links() }}</div>

    {{-- Create / Edit Modal --}}
    <flux:modal name="dealer-form" class="md:w-2xl"
        x-on:open-modal.window="if ($event.detail.name === 'dealer-form') $flux.modal('dealer-form').show()"
        x-on:close-modal.window="if ($event.detail.name === 'dealer-form') $flux.modal('dealer-form').close()"
    >
        <div class="space-y-4" x-data="{ locale: 'en' }">
            <flux:heading>{{ $editingId ? 'Edit Dealer' : 'New Dealer' }}</flux:heading>

            {{-- Locale switcher --}}
            <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2">
                <button type="button"
                    class="px-3 py-1 text-xs font-medium rounded transition-colors"
                    :class="locale === 'en' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-600'"
                    @click="locale = 'en'">EN</button>
                <button type="button"
                    class="px-3 py-1 text-xs font-medium rounded transition-colors"
                    :class="locale === 'bn' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-600'"
                    @click="locale = 'bn'">বাং</button>
            </div>

            {{-- Name --}}
            <div x-show="locale === 'en'">
                <flux:field>
                    <flux:label>Name (English) <flux:badge color="red" size="sm">required</flux:badge></flux:label>
                    <flux:input wire:model="name_en" placeholder="Dealer name in English" />
                    <flux:error name="name_en" />
                </flux:field>
            </div>
            <div x-show="locale === 'bn'">
                <flux:field>
                    <flux:label>নাম (বাংলা)</flux:label>
                    <flux:input wire:model="name_bn" placeholder="বাংলায় ডিলারের নাম" />
                </flux:field>
            </div>

            {{-- Address --}}
            <div x-show="locale === 'en'">
                <flux:field>
                    <flux:label>Address (English)</flux:label>
                    <flux:textarea wire:model="address_en" rows="2" placeholder="Full address…" />
                </flux:field>
            </div>
            <div x-show="locale === 'bn'">
                <flux:field>
                    <flux:label>ঠিকানা (বাংলা)</flux:label>
                    <flux:textarea wire:model="address_bn" rows="2" />
                </flux:field>
            </div>

            {{-- Slug --}}
            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="slug" placeholder="auto-generated" />
                <flux:error name="slug" />
            </flux:field>

            {{-- District + Upazila --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>District <flux:badge color="red" size="sm">required</flux:badge></flux:label>
                    <flux:input wire:model="district" placeholder="e.g. Dhaka" />
                    <flux:error name="district" />
                </flux:field>
                <flux:field>
                    <flux:label>Upazila</flux:label>
                    <flux:input wire:model="upazila" placeholder="e.g. Tejgaon" />
                </flux:field>
            </div>

            {{-- Phone + Email --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Phone <flux:badge color="red" size="sm">required</flux:badge></flux:label>
                    <flux:input wire:model="phone" placeholder="+8801700000000" />
                    <flux:error name="phone" />
                </flux:field>
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="dealer@example.com" />
                    <flux:error name="email" />
                </flux:field>
            </div>

            {{-- Lat + Lng --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Latitude</flux:label>
                    <flux:input type="number" step="0.0000001" wire:model="latitude" placeholder="23.7465" />
                    <flux:error name="latitude" />
                </flux:field>
                <flux:field>
                    <flux:label>Longitude</flux:label>
                    <flux:input type="number" step="0.0000001" wire:model="longitude" placeholder="90.3760" />
                    <flux:error name="longitude" />
                </flux:field>
            </div>

            {{-- Status + Sort Order --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Sort Order</flux:label>
                    <flux:input type="number" wire:model="sort_order" min="0" />
                </flux:field>
            </div>

            {{-- Product Categories --}}
            <div>
                <flux:label class="mb-2 block">Product Categories</flux:label>
                <div class="flex flex-wrap gap-3">
                    @foreach ($this->productCategories as $cat)
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox"
                                wire:model="selectedCategories"
                                value="{{ $cat->id }}"
                                class="rounded border-zinc-300 text-blue-600" />
                            {{ $cat->getTranslation('name', 'en', false) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">Save</flux:button>
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Confirm --}}
    <flux:modal name="dealer-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'dealer-delete') $flux.modal('dealer-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'dealer-delete') $flux.modal('dealer-delete').close()"
    >
        <div class="space-y-4">
            <flux:heading>Delete dealer?</flux:heading>
            <flux:text>This action cannot be undone.</flux:text>
            <div class="flex gap-2">
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
```

- [ ] **Step 3: Commit**

```bash
git add app/Livewire/Admin/Dealers/ resources/views/livewire/admin/dealers/
git commit -m "feat: add Dealers Livewire admin component"
```

---

## Task 5: Public API — Tests then Controller

**Files:**
- Create: `tests/Feature/Dealers/PublicDealerApiTest.php`
- Create: `app/Http/Controllers/Api/V1/DealerController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Add API routes**

In `routes/api.php`, add import at the top with existing use statements:

```php
use App\Http\Controllers\Api\V1\DealerController;
```

Then inside the `prefix('v1')` group, after existing public routes, add:

```php
Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
Route::get('/dealers/{slug}', [DealerController::class, 'show'])->name('dealers.show');
```

- [ ] **Step 2: Write the failing tests**

Create `tests/Feature/Dealers/PublicDealerApiTest.php`:

```php
<?php

use App\Models\Dealer;
use App\Models\ProductCategory;

it('returns only active dealers on public listing', function () {
    Dealer::factory()->create(['name' => ['en' => 'Active One', 'bn' => '']]);
    Dealer::factory()->inactive()->create(['name' => ['en' => 'Hidden', 'bn' => '']]);

    $this->getJson('/api/v1/dealers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active One');
});

it('returns paginated dealers with meta', function () {
    Dealer::factory()->count(5)->create();

    $this->getJson('/api/v1/dealers?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('filters dealers by district', function () {
    Dealer::factory()->create(['district' => 'Dhaka']);
    Dealer::factory()->create(['district' => 'Chattogram']);

    $this->getJson('/api/v1/dealers?district=Dhaka')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters dealers by upazila', function () {
    Dealer::factory()->create(['upazila' => 'Tejgaon']);
    Dealer::factory()->create(['upazila' => 'Mirpur']);

    $this->getJson('/api/v1/dealers?upazila=Tejgaon')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters dealers by product category slug', function () {
    $cat = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    $dealer = Dealer::factory()->create();
    $dealer->categories()->attach($cat->id);
    Dealer::factory()->create();

    $this->getJson("/api/v1/dealers?category={$cat->slug}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches dealers by name', function () {
    Dealer::factory()->create(['name' => ['en' => 'Dhaka Agro Store', 'bn' => '']]);
    Dealer::factory()->create(['name' => ['en' => 'Rajshahi Seeds', 'bn' => '']]);

    $this->getJson('/api/v1/dealers?search=Agro')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Dhaka Agro Store');
});

it('returns dealer detail by slug with categories', function () {
    $cat = ProductCategory::factory()->create();
    $dealer = Dealer::factory()->create(['name' => ['en' => 'Test Dealer', 'bn' => '']]);
    $dealer->categories()->attach($cat->id);

    $this->getJson("/api/v1/dealers/{$dealer->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $dealer->slug)
        ->assertJsonStructure(['data' => [
            'id', 'name', 'slug', 'address', 'district', 'upazila',
            'phone', 'email', 'latitude', 'longitude', 'status',
            'sort_order', 'distance_km', 'categories',
        ]])
        ->assertJsonCount(1, 'data.categories');
});

it('returns 404 for inactive dealer on public endpoint', function () {
    $dealer = Dealer::factory()->inactive()->create();

    $this->getJson("/api/v1/dealers/{$dealer->slug}")->assertNotFound();
});

it('returns nearby dealers with distance_km when lat lng provided', function () {
    // Dealer in Dhaka area
    Dealer::factory()->create(['latitude' => 23.7465, 'longitude' => 90.3760]);
    // Dealer in Chittagong — outside 50km radius from reference point
    Dealer::factory()->create(['latitude' => 22.3569, 'longitude' => 91.7832]);

    $response = $this->getJson('/api/v1/dealers?lat=23.8103&lng=90.4125&radius=50');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.distance_km'))->not->toBeNull();
});

it('nearby search orders dealers by distance ascending', function () {
    Dealer::factory()->create([
        'name'      => ['en' => 'Close Dealer', 'bn' => ''],
        'latitude'  => 23.7503,
        'longitude' => 90.3750,
    ]);
    Dealer::factory()->create([
        'name'      => ['en' => 'Far Dealer', 'bn' => ''],
        'latitude'  => 23.5700,
        'longitude' => 90.2800,
    ]);

    $response = $this->getJson('/api/v1/dealers?lat=23.7465&lng=90.3760&radius=100');

    $response->assertOk();
    expect($response->json('data.0.name'))->toBe('Close Dealer');
    expect($response->json('data.1.name'))->toBe('Far Dealer');
});

it('distance_km is null when no lat lng provided', function () {
    Dealer::factory()->create();

    $response = $this->getJson('/api/v1/dealers');

    $response->assertOk();
    expect($response->json('data.0.distance_km'))->toBeNull();
});

it('returns dealer name in bn locale', function () {
    Dealer::factory()->create(['name' => ['en' => 'English Name', 'bn' => 'বাংলা নাম']]);

    $this->getJson('/api/v1/dealers?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'বাংলা নাম');
});
```

- [ ] **Step 3: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/Dealers/PublicDealerApiTest.php
```

Expected: all fail with "Class not found" or route 404 errors.

- [ ] **Step 4: Create DealerController**

Create `app/Http/Controllers/Api/V1/DealerController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');
        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale  = $this->resolveLocale($request);
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $lat    = $request->query('lat') !== null ? (float) $request->query('lat') : null;
        $lng    = $request->query('lng') !== null ? (float) $request->query('lng') : null;
        $radius = min((float) $request->query('radius', 50), 200);

        $query = Dealer::active()
            ->with('categories')
            ->when($request->query('district'), fn ($q, $v) =>
                $q->where('district', 'like', "%{$v}%"))
            ->when($request->query('upazila'), fn ($q, $v) =>
                $q->where('upazila', 'like', "%{$v}%"))
            ->when($request->query('category'), fn ($q, $slug) =>
                $q->whereHas('categories', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('search'), fn ($q, $search) =>
                $q->where(fn ($sub) => $sub
                    ->where("name->{$locale}", 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")));

        if ($lat !== null && $lng !== null) {
            $latDelta = $radius / 111.0;
            $lngDelta = $radius / (111.0 * cos(deg2rad($lat)));

            $dealers = $query
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereBetween('latitude',  [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->get()
                ->map(function (Dealer $dealer) use ($lat, $lng) {
                    $dealer->distance_km = $this->haversine(
                        $lat, $lng,
                        (float) $dealer->latitude,
                        (float) $dealer->longitude
                    );
                    return $dealer;
                })
                ->filter(fn (Dealer $d) => $d->distance_km <= $radius)
                ->sortBy('distance_km')
                ->values();

            return response()->json([
                'data' => $dealers->map(fn ($d) => $this->formatDealer($d, $locale, withDistance: true)),
                'meta' => ['total' => $dealers->count()],
            ]);
        }

        $dealers = $query->orderBy('sort_order')->paginate($perPage);

        return response()->json([
            'data' => $dealers->map(fn ($d) => $this->formatDealer($d, $locale)),
            'meta' => [
                'current_page' => $dealers->currentPage(),
                'last_page'    => $dealers->lastPage(),
                'per_page'     => $dealers->perPage(),
                'total'        => $dealers->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $dealer = Dealer::active()->with('categories')->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $this->formatDealer($dealer, $locale),
        ]);
    }

    private function formatDealer(Dealer $dealer, string $locale, bool $withDistance = false): array
    {
        return [
            'id'          => $dealer->id,
            'name'        => $dealer->getTranslation('name', $locale, useFallbackLocale: true),
            'slug'        => $dealer->slug,
            'address'     => $dealer->getTranslation('address', $locale, useFallbackLocale: true),
            'district'    => $dealer->district,
            'upazila'     => $dealer->upazila,
            'phone'       => $dealer->phone,
            'email'       => $dealer->email,
            'latitude'    => $dealer->latitude !== null ? (float) $dealer->latitude : null,
            'longitude'   => $dealer->longitude !== null ? (float) $dealer->longitude : null,
            'status'      => $dealer->status,
            'sort_order'  => $dealer->sort_order,
            'distance_km' => $withDistance ? round($dealer->distance_km, 2) : null,
            'categories'  => $dealer->categories->map(fn ($cat) => [
                'id'   => $cat->id,
                'name' => $cat->getTranslation('name', $locale, useFallbackLocale: true),
                'slug' => $cat->slug,
                'icon' => $cat->icon,
            ])->values(),
        ];
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * asin(sqrt($a));
    }
}
```

- [ ] **Step 5: Run tests — expect them to pass**

```bash
php artisan test tests/Feature/Dealers/PublicDealerApiTest.php
```

Expected: all 12 tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Dealers/ app/Http/Controllers/Api/V1/DealerController.php routes/api.php
git commit -m "feat: add public dealer API with proximity search and tests"
```

---

## Task 6: Full Test Suite

- [ ] **Step 1: Run the complete test suite**

```bash
php artisan test
```

Expected: all existing tests plus the 12 new dealer tests pass. No regressions.

- [ ] **Step 2: Commit if any fixes were needed**

```bash
git add -A
git commit -m "fix: resolve any test regressions after dealer feature"
```
