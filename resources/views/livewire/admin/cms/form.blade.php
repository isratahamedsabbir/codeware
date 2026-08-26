<div class="max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ $cmsId ? 'Edit CMS Section' : 'New CMS Section' }}</flux:heading>
        <a href="{{ route('admin.cms') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700">
            &larr; Back to CMS
        </a>
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

            <flux:field>
                <flux:label>Sort Order</flux:label>
                <flux:input type="number" wire:model="sort_order" min="0" />
            </flux:field>
        </div>

        <x-media-picker model="bg_image" label="Background Image" placeholder="Choose a background image from the library" />
    </div>

    {{-- Titles --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Titles</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addTitle">Add title</flux:button>
        </div>

        @forelse ($titles as $i => $title)
            <div class="flex items-start gap-2 rounded-lg border border-zinc-100 p-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>English</flux:label>
                        <flux:input wire:model="titles.{{ $i }}.en" />
                    </flux:field>
                    <flux:field>
                        <flux:label>বাংলা</flux:label>
                        <flux:input wire:model="titles.{{ $i }}.bn" />
                    </flux:field>
                </div>
                <button type="button" wire:click="removeTitle({{ $i }})"
                    class="mt-6 shrink-0 text-rose-500 hover:text-rose-700 cursor-pointer" aria-label="Remove title">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No titles yet.</p>
        @endforelse
    </div>

    {{-- Descriptions --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Descriptions</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addDescription">Add description</flux:button>
        </div>

        @forelse ($descriptions as $i => $description)
            <div class="flex items-start gap-2 rounded-lg border border-zinc-100 p-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>English</flux:label>
                        <flux:textarea wire:model="descriptions.{{ $i }}.en" rows="2" />
                    </flux:field>
                    <flux:field>
                        <flux:label>বাংলা</flux:label>
                        <flux:textarea wire:model="descriptions.{{ $i }}.bn" rows="2" />
                    </flux:field>
                </div>
                <button type="button" wire:click="removeDescription({{ $i }})"
                    class="mt-6 shrink-0 text-rose-500 hover:text-rose-700 cursor-pointer" aria-label="Remove description">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No descriptions yet.</p>
        @endforelse
    </div>

    {{-- Buttons --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Buttons</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addButton">Add button</flux:button>
        </div>

        @forelse ($buttons as $i => $button)
            <div class="flex items-start gap-2 rounded-lg border border-zinc-100 p-3">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Label (English)</flux:label>
                        <flux:input wire:model="buttons.{{ $i }}.label.en" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Label (বাংলা)</flux:label>
                        <flux:input wire:model="buttons.{{ $i }}.label.bn" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Color</flux:label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="buttons.{{ $i }}.color"
                                class="h-9 w-12 rounded border border-zinc-300 cursor-pointer" />
                            <flux:input wire:model="buttons.{{ $i }}.color" placeholder="#2563eb" class="font-mono" />
                        </div>
                    </flux:field>
                    <flux:field>
                        <flux:label>Link</flux:label>
                        <flux:input wire:model="buttons.{{ $i }}.link" placeholder="https:// or /path" />
                    </flux:field>
                </div>
                <button type="button" wire:click="removeButton({{ $i }})"
                    class="mt-6 shrink-0 text-rose-500 hover:text-rose-700 cursor-pointer" aria-label="Remove button">
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
                <div class="flex-1 space-y-3">
                    <x-media-picker model="cards.{{ $i }}.image" label="Card Image" placeholder="Choose a card image" preview="true" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label>Title (English)</flux:label>
                            <flux:input wire:model="cards.{{ $i }}.title.en" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Title (বাংলা)</flux:label>
                            <flux:input wire:model="cards.{{ $i }}.title.bn" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Description (English)</flux:label>
                            <flux:textarea wire:model="cards.{{ $i }}.description.en" rows="2" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Description (বাংলা)</flux:label>
                            <flux:textarea wire:model="cards.{{ $i }}.description.bn" rows="2" />
                        </flux:field>
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

    {{-- Images --}}
    <div class="rounded-lg bg-white shadow-sm border border-zinc-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">Images</flux:heading>
            <flux:button size="xs" variant="outline" wire:click="addImage">Add image</flux:button>
        </div>

        @if (count($images))
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($images as $i => $image)
                    <div class="relative rounded-lg border border-zinc-100 p-3">
                        <button type="button" wire:click="removeImage({{ $i }})"
                            class="absolute top-2 right-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow text-rose-500 hover:bg-rose-50 cursor-pointer"
                            aria-label="Remove image">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <x-media-picker model="images.{{ $i }}" label="" placeholder="Choose an image" />
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-zinc-400">No images yet.</p>
        @endif
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
