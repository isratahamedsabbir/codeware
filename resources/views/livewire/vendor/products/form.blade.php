<div class="max-w-3xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <flux:button variant="ghost" size="sm" icon="arrow-left" href="{{ route('vendor.products') }}" wire:navigate>
            Back
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <flux:heading size="sm">Product Details</flux:heading>
            </div>

            <div class="p-5 space-y-4">
                <x-admin-locale-tabs>
                    @foreach (\App\Support\Locale::active() as $language)
                        <x-admin-locale-panel :code="$language->code" class="space-y-3">
                            <flux:field>
                                <flux:label>
                                    Name
                                    @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                                </flux:label>
                                <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                                    placeholder="{{ $language->code === $this->primaryLocale ? 'Product name' : 'Product name ('.($language->native_name ?: $language->name).')' }}" />
                                @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                            </flux:field>

                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model.live.debounce.400ms="description.{{ $language->code }}" rows="4"
                                    placeholder="{{ $language->code === $this->primaryLocale ? 'Short product summary' : 'Short product summary ('.($language->native_name ?: $language->name).')' }}" />
                                @if ($language->code === $this->primaryLocale)<flux:error name="description.{{ $language->code }}" />@endif
                            </flux:field>
                        </x-admin-locale-panel>
                    @endforeach

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-name" />
                        @if ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
                </x-admin-locale-tabs>

                <flux:field>
                    <flux:label>SKU<x-field-hint text="An internal code for tracking this product. Leave blank if you don't use one." /></flux:label>
                    <flux:input wire:model="sku" placeholder="e.g. TSHIRT-BLK-M" class="font-mono" />
                    <flux:error name="sku" />
                </flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Price</flux:label>
                        <flux:input wire:model="price" type="number" step="0.01" min="0" />
                        <flux:error name="price" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Discount Price</flux:label>
                        <flux:input wire:model="discount_price" type="number" step="0.01" min="0" />
                        <flux:error name="discount_price" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Quantity</flux:label>
                        <flux:input wire:model="quantity" type="number" min="0" />
                        <flux:error name="quantity" />
                    </flux:field>
                </div>

                @if ($this->myVendors->count() > 1)
                    <flux:field>
                        <flux:label>Vendor</flux:label>
                        <select wire:model="vendor_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="">Select vendor</option>
                            @foreach ($this->myVendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <flux:error name="vendor_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Brand</flux:label>
                    <select wire:model="brand_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                        <option value="">No brand</option>
                        @foreach ($this->productBrands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="brand_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Categories</flux:label>
                    <flux:checkbox.group wire:model="category_ids" class="flex-col items-stretch gap-0 max-h-72 overflow-y-auto border border-zinc-200 rounded-lg p-2">
                        @forelse ($this->categoryTree as $cat)
                            <div class="rounded-md py-0 hover:bg-zinc-50 transition-colors [&_ui-label]:text-xs [&_ui-label]:leading-4 [&_ui-checkbox]:size-4" style="padding-left: {{ 8 + $cat->depth * 8 }}px">
                                <flux:checkbox value="{{ $cat->id }}"
                                    label="{{ $cat->getTranslation('name', \App\Support\Locale::primary(), false) }}" />
                            </div>
                        @empty
                            <p class="text-xs text-zinc-400 px-2 py-1">No categories yet.</p>
                        @endforelse
                    </flux:checkbox.group>
                    <flux:error name="category_ids" />
                </flux:field>

                <flux:field>
                    <flux:label>Thumbnail Image<x-field-hint text="Recommended 800×800px, max 2MB." /></flux:label>
                    <div class="flex items-center gap-4">
                        @if ($featuredImage)
                            <img src="{{ $featuredImage->temporaryUrl() }}" alt="Preview" class="size-16 rounded-lg object-cover border border-zinc-200">
                        @elseif ($existingFeaturedImage)
                            <img src="{{ $existingFeaturedImage }}" alt="Current image" class="size-16 rounded-lg object-cover border border-zinc-200">
                        @endif
                        <flux:input type="file" wire:model="featuredImage" accept="image/jpeg,image/png,image/webp" />
                    </div>
                    <flux:error name="featuredImage" />
                </flux:field>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ $productId ? 'Save Changes' : 'Create Product' }}</flux:button>
            <flux:button variant="ghost" href="{{ route('vendor.products') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
