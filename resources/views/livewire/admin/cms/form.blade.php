@push('page-header-actions')
    <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.cms', ['pageId' => $pageId]) }}" wire:navigate>
        Back
    </flux:button>
@endpush

<div class="w-full space-y-5">

    {{-- Basics --}}
    <x-admin-section-card variant="postbox" persist-key="cms-basics" :title="$page->getTranslation('title', 'en', false)" :collapsed="false">
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
    <x-admin-section-card variant="postbox" persist-key="cms-cards" title="Cards"
        description="Repeatable image/title/description tiles for this section."
        :collapsed="true">
        <x-slot:titleActions>
            {{-- Into the Developer Guide rather than a copy of it. This modal said
                 a file constant "resolves to" its public URL when the picker
                 stores the URL as the value outright, and never mentioned that
                 cards cannot be reordered at all. --}}
            <a href="{{ route('admin.developer-guide') }}#section-cards"
                title="How section cards work — open the Developer Guide"
                aria-label="How section cards work — open the Developer Guide"
                class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                <flux:icon.information-circle class="size-4" />
            </a>
        </x-slot:titleActions>
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

                    <x-media-picker model="cards.{{ $i }}.image" label="Card Image" size-hint="600 × 400" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
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
    <x-admin-section-card variant="postbox" persist-key="cms-constant" title="Constant"
        description="Freeform key/value pairs — SEO tags, custom flags, or extra content." :collapsed="true">
        <x-slot:titleActions>
            {{-- Into the Developer Guide rather than a copy of it — this modal
                 carried the same stale "resolves to its public URL" claim and
                 never said a renamed section silently returns null. --}}
            <a href="{{ route('admin.developer-guide') }}#section-constants"
                title="How section constants work — open the Developer Guide"
                aria-label="How section constants work — open the Developer Guide"
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

    {{-- Save --}}
    <div class="flex items-center gap-3">
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            {{ $cmsId ? 'Update Section' : 'Create Section' }}
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="cms-form-picker-modal" />

    

    

</div>
