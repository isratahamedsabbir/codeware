<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.pages') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 space-y-5">
        <x-admin-section-card variant="postbox" persist-key="page-details" title="Page Details" :collapsed="false">
            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::translatable() as $language)
                    <x-admin-locale-panel :code="$language->code">
                        <flux:field>
                            <flux:label :badge="$language->code">
                                Title
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model.live.debounce.400ms="title.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Page title' : 'Page title ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="title.{{ $language->code }}" />@endif
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-title" :disabled="$this->isLinked()" />
                        @if ($this->isLinked())
                            <p class="text-xs text-zinc-400 mt-1">Managed on the linked product/post/category — edit it from there</p>
                        @elseif ($slugAvailable === false)
                            <p class="text-xs text-red-500 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>This slug is already taken</p>
                        @elseif ($slugAvailable === true && $slug !== '')
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>This slug is available</p>
                        @else
                            <p class="text-xs text-zinc-400 mt-1">Auto-generated from the primary language's title as you type — edit it if you'd like a different one</p>
                        @endif
                        <flux:error name="slug" />
                    </flux:field>
            </x-admin-locale-tabs>
        </x-admin-section-card>

        @include('partials._admin-seo-fields', ['seoCardVariant' => 'postbox', 'seoPersistKey' => 'page-seo'])

        {{-- ── Constant ── --}}
        <x-admin-section-card variant="postbox" persist-key="page-constant" title="Constant"
            description="Freeform key/value pairs — custom flags or extra content." :collapsed="true">
            <x-slot:titleActions>
                {{-- Points into the Developer Guide rather than opening a copy of
                     it. This modal restated what the guide says about page
                     constants and repeated the stale "a file constant resolves
                     to its public URL" line - the picker stores the URL as the
                     value, nothing resolves it - while omitting the key rules
                     that decide whether the save succeeds.

                     A plain anchor rather than wire.navigate: a full load is what
                     honours the fragment and lands on the heading. --}}
                <a href="{{ route('admin.developer-guide') }}#page-constants"
                    title="How page constants work — open the Developer Guide"
                    aria-label="How page constants work — open the Developer Guide"
                    class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                    <flux:icon.information-circle class="size-4" />
                </a>
            </x-slot:titleActions>
            <x-slot:actions>
                <flux:button size="sm" variant="outline" icon="plus" wire:click="addConstant" x-on:click="open = true">Add field</flux:button>
            </x-slot:actions>

                <flux:error name="constant" />

                <x-admin-constant-fields :items="$constant" :open-constants="$openConstants" model="constant" key-placeholder="e.g. og_type" value-placeholder="e.g. website" empty-title="No content yet" />
        </x-admin-section-card>

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$pageId ? 'Update Page' : 'Create Page'" />
        </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-5">
            <x-admin-section-card variant="postbox" persist-key="page-settings" title="Page Settings" :collapsed="false"
                description="Template used to render this page.">
                <flux:field>
                    <flux:label>Template</flux:label>
                    <flux:input wire:model="template" placeholder="puck" />
                </flux:field>
            </x-admin-section-card>

            <livewire:admin.media-library.picker-modal key="pages-form-picker-modal" />
        </div>
    </div>

    

</div>
