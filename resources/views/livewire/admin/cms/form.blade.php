@push('page-header-actions')
    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.cms') }}" wire:navigate
        class="border border-zinc-800 rounded!">
        Back
    </flux:button>
@endpush

<div class="w-full space-y-6" x-data="{ locale: 'en' }">

    {{-- Language toggle — switches every title/description/label/card field below
         between English and বাংলা, so each pair only takes up one field's worth
         of space instead of showing both side by side. --}}
    <div class="flex gap-2">
        <button type="button"
            :class="locale === 'en' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
            class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
            @click="locale='en'">EN</button>
        <button type="button"
            :class="locale === 'bn' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
            class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
            @click="locale='bn'">বাং</button>
    </div>

    {{-- Basics --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <flux:heading size="sm">Basics</flux:heading>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Page</flux:label>
                <flux:input wire:model="page" placeholder="e.g. home, about, contact" />
                <flux:error name="page" />
            </flux:field>

            <flux:field>
                <flux:label>Section</flux:label>
                <flux:input wire:model="section" placeholder="e.g. hero, features, cta" />
                <flux:error name="section" />
            </flux:field>
        </div>
    </div>

    {{-- Title, Description, Image & Background Image --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5">
        <flux:heading size="sm" class="mb-4">Content</flux:heading>

        <div class="space-y-4">
            <flux:field x-show="locale === 'en'">
                <flux:label>Title (English)</flux:label>
                <flux:input wire:model="title.en" placeholder="e.g. Welcome to Codeware" class="font-medium" />
            </flux:field>
            <flux:field x-show="locale === 'bn'" x-cloak>
                <flux:label>Title (বাংলা)</flux:label>
                <flux:input wire:model="title.bn" placeholder="যেমন: কোডওয়্যারে স্বাগতম" class="font-medium" />
            </flux:field>

            <flux:field x-show="locale === 'en'">
                <flux:label>Description (English)</flux:label>
                <flux:textarea wire:model="description.en" rows="8" placeholder="Short description shown for this section" />
            </flux:field>
            <flux:field x-show="locale === 'bn'" x-cloak>
                <flux:label>Description (বাংলা)</flux:label>
                <flux:textarea wire:model="description.bn" rows="8" placeholder="এই সেকশনের জন্য সংক্ষিপ্ত বিবরণ" />
            </flux:field>

            <x-media-picker model="image" label="Image" />

            <x-media-picker model="bg_image" label="Background Image" />
        </div>
    </div>

    {{-- Buttons --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Buttons</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addButton">Add button</flux:button>
        </div>

        @forelse ($buttons as $i => $button)
            <div class="flex items-start gap-2 rounded-lg border border-zinc-100 p-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <flux:field x-show="locale === 'en'">
                        <flux:label>Label (English)</flux:label>
                        <flux:input wire:model="buttons.{{ $i }}.label.en" />
                    </flux:field>
                    <flux:field x-show="locale === 'bn'" x-cloak>
                        <flux:label>Label (বাংলা)</flux:label>
                        <flux:input wire:model="buttons.{{ $i }}.label.bn" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Color</flux:label>
                        <div class="flex items-center rounded-lg border border-zinc-300 overflow-hidden focus-within:ring-2 focus-within:ring-zinc-400">
                            <input type="color" wire:model.live="buttons.{{ $i }}.color"
                                class="h-9 w-10 shrink-0 cursor-pointer border-0 border-r border-zinc-300 p-0" />
                            <input type="text" wire:model="buttons.{{ $i }}.color" placeholder="#2563eb"
                                class="h-9 min-w-0 flex-1 border-0 px-3 text-sm font-mono focus:outline-none focus:ring-0" />
                        </div>
                    </flux:field>
                    <flux:field>
                        <flux:label>Link</flux:label>
                        <flux:input wire:model="buttons.{{ $i }}.link" placeholder="https:// or /path" />
                    </flux:field>
                </div>
                <button type="button" wire:click="removeButton({{ $i }})"
                    class="mt-1 shrink-0 text-rose-500 hover:text-rose-700 cursor-pointer" aria-label="Remove button">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No buttons yet.</p>
        @endforelse
    </div>

    {{-- Cards --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Cards</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addCard">Add card</flux:button>
        </div>

        @forelse ($cards as $i => $card)
            <div class="flex items-start gap-2 rounded-lg border border-zinc-100 p-3">
                <div class="flex-1 grid grid-cols-1 lg:grid-cols-[3fr_1fr] gap-x-7 gap-y-4">

                    {{-- Left: title & description --}}
                    <div class="space-y-4 min-w-0">
                        <flux:field x-show="locale === 'en'">
                            <flux:label>Title (English)</flux:label>
                            <flux:input wire:model="cards.{{ $i }}.title.en" placeholder="e.g. Fast Delivery" class="font-medium" />
                        </flux:field>
                        <flux:field x-show="locale === 'bn'" x-cloak>
                            <flux:label>Title (বাংলা)</flux:label>
                            <flux:input wire:model="cards.{{ $i }}.title.bn" placeholder="যেমন: দ্রুত ডেলিভারি" class="font-medium" />
                        </flux:field>
                        <flux:field x-show="locale === 'en'">
                            <flux:label>Description (English)</flux:label>
                            <flux:textarea wire:model="cards.{{ $i }}.description.en" rows="8" placeholder="Short description shown on the card" />
                        </flux:field>
                        <flux:field x-show="locale === 'bn'" x-cloak>
                            <flux:label>Description (বাংলা)</flux:label>
                            <flux:textarea wire:model="cards.{{ $i }}.description.bn" rows="8" placeholder="কার্ডে দেখানো সংক্ষিপ্ত বিবরণ" />
                        </flux:field>
                    </div>

                    {{-- Right: card image --}}
                    <div class="min-w-0">
                        <x-media-picker model="cards.{{ $i }}.image" label="Card Image" />
                    </div>

                </div>
                <button type="button" wire:click="removeCard({{ $i }})"
                    class="mt-1 shrink-0 text-rose-500 hover:text-rose-700 cursor-pointer" aria-label="Remove card">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No cards yet.</p>
        @endforelse
    </div>

    {{-- Metadata --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Metadata</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addMetadata">Add field</flux:button>
        </div>
        <flux:error name="metadata" />

        @forelse ($metadata as $i => $pair)
            <div class="flex items-start gap-2 rounded-lg border border-zinc-100 p-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Key</flux:label>
                        <flux:input wire:model="metadata.{{ $i }}.key" placeholder="e.g. og:type" class="font-mono" />
                        <flux:error name="metadata.{{ $i }}.key" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Value</flux:label>
                        <flux:input wire:model="metadata.{{ $i }}.value" placeholder="e.g. website" />
                        <flux:error name="metadata.{{ $i }}.value" />
                    </flux:field>
                </div>
                <button type="button" wire:click="removeMetadata({{ $i }})"
                    class="mt-1 shrink-0 text-rose-500 hover:text-rose-700 cursor-pointer" aria-label="Remove field">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No metadata yet.</p>
        @endforelse
    </div>

    {{-- Save --}}
    <div class="flex items-center gap-3">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            {{ $cmsId ? 'Update Section' : 'Create Section' }}
        </flux:button>
        <a href="{{ route('admin.cms') }}" wire:navigate
            class="px-4 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-800">
            Cancel
        </a>
    </div>

    <livewire:admin.media-library.picker-modal />

</div>
