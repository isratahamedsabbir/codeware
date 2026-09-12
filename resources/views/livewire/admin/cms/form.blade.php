@push('page-header-actions')
    <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.cms', ['pageId' => $pageId]) }}" wire:navigate>
        Back
    </flux:button>
@endpush

<div class="w-full space-y-6">

    {{-- Basics --}}
    <x-admin-section-card icon="squares-2x2" :title="$page->getTranslation('title', 'en', false)">
        <flux:field>
            <div class="flex rounded-lg border border-zinc-300 overflow-hidden focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20">
                <span class="flex items-center px-3 bg-zinc-50 border-r border-zinc-300 text-sm text-zinc-500">
                    Name
                </span>
                <input type="text" wire:model.live="name" placeholder="e.g. hero, features, cta"
                    class="flex-1 min-w-0 border-0 px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none focus:ring-0" />
            </div>
            <flux:error name="name" />
        </flux:field>
    </x-admin-section-card>

    {{-- Cards --}}
    <x-admin-section-card icon="rectangle-group" title="Cards" icon-color="bg-blue-500/10 text-blue-600"
        description="Repeatable image/title/description tiles for this section."
        collapsible :collapsed="true">
        <x-slot:actions>
            <flux:button size="sm" variant="outline" icon="plus" wire:click="addCard" x-on:click="open = true">Add card</flux:button>
        </x-slot:actions>

        <div class="space-y-3">
        @forelse ($cards as $i => $card)
            @php $cardOpen = in_array($i, $openCards, true); @endphp
            <div wire:key="cms-card-row-{{ $i }}" class="group relative rounded-[5px] border border-zinc-200 bg-zinc-50/60 overflow-hidden transition-colors hover:border-zinc-300">
                <div wire:click="toggleCard({{ $i }})" role="button" tabindex="0"
                    class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-zinc-200 bg-white cursor-pointer select-none">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-[11px] font-semibold text-zinc-500">
                            {{ $i + 1 }}
                        </div>
                        <flux:heading size="sm" class="truncate">
                            {{ ($card['title'] ?? '') !== '' ? $card['title'] : 'Card '.($i + 1) }}
                        </flux:heading>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" wire:click.stop="removeCard({{ $i }})"
                            class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove card">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button type="button" wire:click.stop="toggleCard({{ $i }})"
                            class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 cursor-pointer"
                            aria-expanded="{{ $cardOpen ? 'true' : 'false' }}" aria-label="Toggle card">
                            <flux:icon.chevron-down class="size-4 transition-transform {{ $cardOpen ? 'rotate-180' : '' }}" />
                        </button>
                    </div>
                </div>

                <div class="p-4 space-y-3 {{ $cardOpen ? '' : 'hidden' }}">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model.live="cards.{{ $i }}.title" placeholder="e.g. Fast Delivery" class="font-medium" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="cards.{{ $i }}.description" class="h-24" placeholder="Short description shown on the card" />
                    </flux:field>

                    <x-media-picker model="cards.{{ $i }}.image" label="Card Image" hint="600×400px" mimes="jpg,jpeg,png,webp" only-images dropzone />
                </div>
            </div>
        @empty
            <div class="rounded-[5px] border border-dashed border-zinc-200 py-10 text-center">
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
    </x-admin-section-card>

    {{-- Constant --}}
    <x-admin-section-card icon="variable" title="Constant" icon-color="bg-indigo-500/10 text-indigo-600"
        description="Freeform key/value pairs — SEO tags, custom flags, or extra content." collapsible :collapsed="true">
        <x-slot:actions>
            <flux:button size="sm" variant="outline" icon="plus" wire:click="addConstant" x-on:click="open = true">Add field</flux:button>
        </x-slot:actions>
        <flux:error name="constant" />

        <div class="space-y-3">
        @forelse ($constant as $i => $pair)
            @php $constantOpen = in_array($i, $openConstants, true); @endphp
            <div wire:key="constant-row-{{ $i }}"
                class="group relative rounded-[5px] border border-zinc-200 bg-zinc-50/60 overflow-hidden transition-colors hover:border-zinc-300">
                <div wire:click="toggleConstant({{ $i }})" role="button" tabindex="0"
                    class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-zinc-200 bg-white cursor-pointer select-none">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-[11px] font-semibold text-zinc-500">
                            {{ $i + 1 }}
                        </div>
                        <flux:heading size="sm" class="truncate font-mono">
                            {{ ($pair['key'] ?? '') !== '' ? $pair['key'] : 'Field '.($i + 1) }}
                        </flux:heading>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" wire:click.stop="removeConstant({{ $i }})"
                            class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove field">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button type="button" wire:click.stop="toggleConstant({{ $i }})"
                            class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 cursor-pointer"
                            aria-expanded="{{ $constantOpen ? 'true' : 'false' }}" aria-label="Toggle field">
                            <flux:icon.chevron-down class="size-4 transition-transform {{ $constantOpen ? 'rotate-180' : '' }}" />
                        </button>
                    </div>
                </div>

                <div class="p-4 space-y-3 {{ $constantOpen ? '' : 'hidden' }}">
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
            <div class="rounded-[5px] border border-dashed border-zinc-200 py-10 text-center">
                <svg class="mx-auto mb-2 h-8 w-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <p class="text-sm text-zinc-400">No content yet.</p>
            </div>
        @endforelse
        </div>
    </x-admin-section-card>

    {{-- Save --}}
    <div class="flex items-center gap-3">
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            {{ $cmsId ? 'Update Section' : 'Create Section' }}
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="cms-form-picker-modal" />

</div>
