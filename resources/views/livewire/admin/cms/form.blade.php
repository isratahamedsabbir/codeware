@if ($cmsId)
    @push('page-header-actions')
        <flux:button variant="ghost" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.cms', ['pageId' => $pageId]) }}" wire:navigate>
            Back
        </flux:button>
    @endpush
@endif

<div class="w-full space-y-6">

    {{-- Basics --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <flux:heading size="sm">{{ $page->getTranslation('title', 'en', false) }}</flux:heading>

        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model.live="name" placeholder="e.g. hero, features, cta" />
            <flux:error name="name" />
        </flux:field>
    </div>

    {{-- Cards --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="sm">Cards</flux:heading>
                <p class="mt-0.5 text-xs text-zinc-400">Repeatable image/title/description tiles for this section.</p>
            </div>
            <flux:button size="xs" variant="outline" icon="plus" wire:click="addCard">Add card</flux:button>
        </div>

        @forelse ($cards as $i => $card)
            <div class="group relative rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 transition-colors hover:border-zinc-300">
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                        {{ $i + 1 }}
                    </div>

                    <div class="flex-1 min-w-0 space-y-3">
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="cards.{{ $i }}.title" placeholder="e.g. Fast Delivery" class="font-medium" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="cards.{{ $i }}.description" class="h-48" placeholder="Short description shown on the card" />
                        </flux:field>

                        <x-media-picker model="cards.{{ $i }}.image" label="Card Image" dropzone />
                    </div>

                    <button type="button" wire:click="removeCard({{ $i }})"
                        class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove card">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-200 py-10 text-center">
                <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                </svg>
                <p class="text-sm text-zinc-400">No cards yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Content --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="sm">Content</flux:heading>
                <p class="mt-0.5 text-xs text-zinc-400">Freeform key/value pairs — SEO tags, custom flags, or extra content.</p>
            </div>
            <flux:button size="xs" variant="outline" icon="plus" wire:click="addMetadata">Add field</flux:button>
        </div>
        <flux:error name="metadata" />

        @forelse ($metadata as $i => $pair)
            <div class="group relative rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 transition-colors hover:border-zinc-300">
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                        {{ $i + 1 }}
                    </div>

                    <div class="flex-1 min-w-0 space-y-3">
                        <flux:field>
                            <flux:label>Value type</flux:label>
                            <div class="grid grid-cols-3 gap-1.5 rounded-lg bg-zinc-100 p-1">
                                <button type="button" wire:click="$set('metadata.{{ $i }}.type', 'text')"
                                    class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'text') === 'text' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V4h16v3M9 20h6M12 4v16" />
                                    </svg>
                                    Input
                                </button>
                                <button type="button" wire:click="$set('metadata.{{ $i }}.type', 'textarea')"
                                    class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'text') === 'textarea' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                                    </svg>
                                    Textarea
                                </button>
                                <button type="button" wire:click="$set('metadata.{{ $i }}.type', 'file')"
                                    class="flex items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium transition-colors cursor-pointer {{ ($pair['type'] ?? 'text') === 'file' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
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
                            <flux:input wire:model.live="metadata.{{ $i }}.key" placeholder="e.g. og_type" class="font-mono" />
                            <flux:error name="metadata.{{ $i }}.key" />
                        </flux:field>

                        @if (($pair['type'] ?? 'text') === 'textarea')
                            <flux:field>
                                <flux:label>Value</flux:label>
                                <flux:textarea wire:model="metadata.{{ $i }}.value" class="h-48" placeholder="e.g. website" />
                                <flux:error name="metadata.{{ $i }}.value" />
                            </flux:field>
                        @elseif (($pair['type'] ?? 'text') === 'file')
                            <x-media-picker model="metadata.{{ $i }}.value" label="Value" dropzone />
                            <flux:error name="metadata.{{ $i }}.value" />
                        @else
                            <flux:field>
                                <flux:label>Value</flux:label>
                                <flux:input wire:model="metadata.{{ $i }}.value" placeholder="e.g. website" />
                                <flux:error name="metadata.{{ $i }}.value" />
                            </flux:field>
                        @endif
                    </div>

                    <button type="button" wire:click="removeMetadata({{ $i }})"
                        class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-200 py-10 text-center">
                <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <p class="text-sm text-zinc-400">No metadata yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Save --}}
    <div class="flex items-center gap-3">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            {{ $cmsId ? 'Update Section' : 'Create Section' }}
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal />

</div>
