<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.types') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <x-admin-section-card variant="postbox" title="Type Details" :collapsible="false" class="w-full max-w-2xl">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::translatable() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label :badge="$language->code">
                            Name
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'e.g. Product, Post' : 'e.g. Product, Post ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach
        </x-admin-locale-tabs>

        {{-- Slug is the stable key the rest of the system keys off (Type::idFor(),
             pages.type, the product/post pool subqueries) rather than anything
             cosmetic, so the hint spells that out instead of just saying
             "auto-generated". --}}
        <flux:field>
            <flux:label>Slug</flux:label>
            <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-name" />
            <p class="text-xs text-zinc-400 mt-1">
                Leave blank to generate it from the primary language's name. The slug is what the code keys off to tell the product pool from the post pool, so renaming it later re-points that split.
            </p>
            <flux:error name="slug" />
        </flux:field>

        <flux:field>
            <flux:label>Sort order<span class="text-red-500 ml-0.5">*</span><x-field-hint text="Lower numbers appear first in the type dropdowns on the category, brand and tag forms." /></flux:label>
            <flux:input type="number" min="0" wire:model.live.debounce.400ms="sortOrder" placeholder="0" />
            <flux:error name="sortOrder" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$typeId ? 'Update Type' : 'Create Type'" />
        </div>

    </x-admin-section-card>
</div>
