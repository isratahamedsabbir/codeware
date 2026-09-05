@push('page-header-actions')
    <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.cms', ['pageId' => $pageId]) }}" wire:navigate>
        Back
    </flux:button>
@endpush

<div class="w-full space-y-6">

    {{-- Basics --}}
    <x-admin-section-card icon="squares-2x2" :title="$page->getTranslation('title', 'en', false)">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model.live="name" placeholder="e.g. hero, features, cta" />
            <flux:error name="name" />
        </flux:field>
    </x-admin-section-card>

    {{-- Cards --}}
    <x-admin-section-card icon="rectangle-group" title="Cards" icon-color="bg-blue-500/10 text-blue-600"
        description="Repeatable image/title/description tiles for this section.">
        <x-slot:actions>
            <flux:button size="xs" variant="outline" icon="plus" wire:click="addCard">Add card</flux:button>
        </x-slot:actions>

        @forelse ($cards as $i => $card)
            <div class="group relative rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 transition-colors hover:border-zinc-300">
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                        {{ $i + 1 }}
                    </div>

                    <div class="flex-1 min-w-0 space-y-3">
                        <flux:field>
                            <flux:label class="text-[11px]! font-bold! uppercase! tracking-wider! text-zinc-500! dark:text-zinc-400! block! w-full! pb-2.5! mb-3! border-b! border-zinc-200! dark:border-zinc-700!">Title</flux:label>
                            <flux:input wire:model="cards.{{ $i }}.title" placeholder="e.g. Fast Delivery" class="font-medium" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="cards.{{ $i }}.description" class="h-24" placeholder="Short description shown on the card" />
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
    </x-admin-section-card>

    {{-- Constant --}}
    <x-admin-section-card icon="variable" title="Constant" icon-color="bg-indigo-500/10 text-indigo-600"
        description="Freeform key/value pairs — SEO tags, custom flags, or extra content.">
        <x-slot:actions>
            <flux:button size="xs" variant="outline" icon="plus" wire:click="addConstant">Add field</flux:button>
        </x-slot:actions>
        <flux:error name="constant" />

        @forelse ($constant as $i => $pair)
            <div wire:key="constant-row-{{ $i }}" class="group relative rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 transition-colors hover:border-zinc-300">
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-[11px] font-semibold text-white">
                        {{ $i + 1 }}
                    </div>

                    <div class="flex-1 min-w-0 space-y-3">
                        <flux:field>
                            <flux:label class="text-[11px]! font-bold! uppercase! tracking-wider! text-zinc-500! dark:text-zinc-400! block! w-full! pb-2.5! mb-3! border-b! border-zinc-200! dark:border-zinc-700!">Value type</flux:label>
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

                    <button type="button" wire:click="removeConstant({{ $i }})"
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
                <p class="text-sm text-zinc-400">No content yet.</p>
            </div>
        @endforelse
    </x-admin-section-card>

    {{-- Save --}}
    <div class="flex items-center gap-3">
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            {{ $cmsId ? 'Update Section' : 'Create Section' }}
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="cms-form-picker-modal" />

</div>
