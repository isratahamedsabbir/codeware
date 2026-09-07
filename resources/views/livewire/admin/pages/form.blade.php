<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.pages') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 space-y-4">
        <div class="bg-white rounded-[5px] shadow-sm p-6">
            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::active() as $language)
                    <x-admin-locale-panel :code="$language->code">
                        <flux:field>
                            <flux:label>
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
        </div>

        @include('partials.admin-seo-fields')

        {{-- ── Constant ── --}}
        <x-admin-section-card icon="variable" title="Constant" icon-color="bg-indigo-500/10 text-indigo-600"
            description="Freeform key/value pairs — custom flags or extra content.">
            <x-slot:actions>
                <flux:button size="xs" variant="outline" icon="plus" wire:click="addConstant">Add field</flux:button>
            </x-slot:actions>

                <flux:error name="constant" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse ($constant as $i => $pair)
                    <div wire:key="constant-row-{{ $i }}" class="group relative rounded-[5px] border border-zinc-200 bg-zinc-50/60 overflow-hidden transition-colors hover:border-zinc-300">
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-zinc-200 bg-white">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-[11px] font-semibold text-zinc-500">
                                    {{ $i + 1 }}
                                </div>
                                <flux:heading size="sm">Field</flux:heading>
                            </div>

                            <button type="button" wire:click="removeConstant({{ $i }})"
                                class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 space-y-3">
                            <flux:field>
                                <div class="grid grid-cols-2 gap-1.5 rounded-lg bg-zinc-100 p-1">
                                    <button type="button" wire:click="setConstantType({{ $i }}, 'textarea')"
                                        class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'textarea') === 'textarea' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                                        </svg>
                                        Textarea
                                    </button>
                                    <button type="button" wire:click="setConstantType({{ $i }}, 'file')"
                                        class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'textarea') === 'file' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                            <polyline points="14 2 14 8 20 8" />
                                        </svg>
                                        File
                                    </button>
                                </div>
                            </flux:field>

                            <flux:field>
                                <flux:label>Key</flux:label>
                                <flux:input wire:model.live="constant.{{ $i }}.key" placeholder="e.g. og_type" class="font-mono" />
                                <flux:error name="constant.{{ $i }}.key" />
                            </flux:field>

                            @if (($pair['type'] ?? 'textarea') === 'file')
                                <x-media-picker model="constant.{{ $i }}.value" label="Value" dropzone />
                                <flux:error name="constant.{{ $i }}.value" />
                            @else
                                <flux:field>
                                    <flux:label>Value</flux:label>
                                    <flux:textarea wire:model="constant.{{ $i }}.value" class="h-24" placeholder="e.g. website" />
                                    <flux:error name="constant.{{ $i }}.value" />
                                </flux:field>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-[5px] border border-dashed border-zinc-200 py-10 text-center">
                        <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <p class="text-sm text-zinc-400">No content yet.</p>
                    </div>
                @endforelse
                </div>
        </x-admin-section-card>

        <div class="flex items-center gap-3 flex-wrap">
            <x-admin-save-button :label="$pageId ? 'Update Page' : 'Create Page'" />
        </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">
            <x-admin-section-card icon="cog-6-tooth" title="Page Settings" body-class="px-4 py-3"
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
