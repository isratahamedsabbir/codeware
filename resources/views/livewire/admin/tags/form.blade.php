<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.tags') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <x-admin-section-card variant="postbox" title="Tag Details" :collapsible="false" class="w-full" body-class="p-3">

            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::translatable() as $language)
                    <x-admin-locale-panel :code="$language->code">
                        <flux:field>
                            <flux:label :badge="$language->code">
                                Name
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model="name.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Tag name' : 'Tag name ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                <flux:field>
                    <flux:label>Type<span class="text-red-500 ml-0.5">*</span><x-field-hint text="Post tags show on the Post form, product tags on the Product form. Manage the list of types under Accessories → Types" /></flux:label>
                    <flux:select wire:model="typeId" placeholder="Select a type">
                        @foreach ($this->typeOptions as $type)
                            <flux:select.option value="{{ $type->id }}">{{ $type->getTranslation('name', $this->primaryLocale, false) ?: $type->getTranslation('name', 'en', false) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="typeId" />
                </flux:field>
            </x-admin-locale-tabs>

        {{-- Footer --}}
        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                </svg>
                <span wire:loading.remove wire:target="save">{{ $tagId ? 'Update Tag' : 'Create Tag' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </x-admin-section-card>
</div>
