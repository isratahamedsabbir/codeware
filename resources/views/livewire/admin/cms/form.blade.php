@push('page-header-actions')
    <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.cms', ['pageId' => $pageId]) }}" wire:navigate>
        Back
    </flux:button>
@endpush

<div class="w-full space-y-6" x-data="{ showCardsGuide: false, showConstantGuide: false }">

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
        <x-slot:titleActions>
            <button type="button" @click="showCardsGuide = true" title="How sections' cards work"
                class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                <flux:icon.information-circle class="size-4" />
            </button>
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

                    <x-media-picker model="cards.{{ $i }}.image" label="Card Image" size-hint="600 × 400" mimes="jpg,jpeg,png,webp" only-images dropzone />
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
        <x-slot:titleActions>
            <button type="button" @click="showConstantGuide = true" title="How section constants work"
                class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                <flux:icon.information-circle class="size-4" />
            </button>
        </x-slot:titleActions>
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

    {{-- ── Cards guide modal ── --}}
    <div x-show="showCardsGuide" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @keydown.escape.window="showCardsGuide = false">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
            @click.away="showCardsGuide = false">

            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                <h3 class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    <flux:icon.rectangle-group class="size-4 text-blue-600" />
                    How Section Cards Work
                </h3>
                <button type="button" @click="showCardsGuide = false"
                    class="rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-4 p-6 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                <p>
                    A <strong>card</strong> is a repeatable tile — image, title and short description. Add as many as you
                    like; they belong to the section (this CMS entry), not a single page.
                </p>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">1. Fill in each card</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Give the section a <em>Name</em> (e.g. <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">features</code>),
                        then add cards with an image (600&times;400px), title and description — or leave fields blank to use placeholders.
                    </p>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">2. Read them in a view</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Pull the whole tile list with <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">cms_cards()</code> — page slug + section name:</p>
                    <pre class="mt-2 overflow-x-auto rounded-md bg-zinc-900 p-3 font-mono text-[11px] leading-relaxed text-zinc-100"><code>@{{-- page slug 'home', section name 'features' --}}
@@foreach (cms_cards('home', 'features') as $card)
    &lt;img src="@{{ $card['image'] }}"&gt;
    &lt;h3&gt;@{{ $card['title'] }}&lt;/h3&gt;
    &lt;p&gt;@{{ $card['description'] }}&lt;/p&gt;
@@endforeach</code></pre>
                </div>

                <p class="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50/70 p-4 text-xs text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                    <flux:icon.light-bulb class="mt-0.5 size-4 shrink-0" />
                    <span>
                        On the API, this section's cards appear under
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">data.cms[...].cards</code>.
                        Cards render in order — reorder them by collapsing and dragging is not available, so delete and re-add to change order.
                    </span>
                </p>
            </div>

            <div class="flex items-center justify-end border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <flux:button variant="primary" size="sm" @click="showCardsGuide = false">Got it</flux:button>
            </div>
        </div>
    </div>

    {{-- ── Constant guide modal ── --}}
    <div x-show="showConstantGuide" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @keydown.escape.window="showConstantGuide = false">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
            @click.away="showConstantGuide = false">

            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                <h3 class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    <flux:icon.variable class="size-4 text-primary" />
                    How Section Constants Work
                </h3>
                <button type="button" @click="showConstantGuide = false"
                    class="rounded p-1 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-4 p-6 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                <p>
                    A <strong>section constant</strong> is a key/value pair scoped to <em>this</em> CMS section — useful
                    for SEO tags, labels or flags specific to the section, not the whole page or site.
                </p>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">1. Give it a unique key</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Keys use letters, numbers and underscores — e.g.
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">og_type</code>.
                        Set the value and save the section.
                    </p>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">2. Read it by page slug + section name + key</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Use the helper from any theme template:</p>
                    <pre class="mt-2 overflow-x-auto rounded-md bg-zinc-900 p-3 font-mono text-[11px] leading-relaxed text-zinc-100"><code>@{{-- page slug 'home', section name 'cta', key 'heading' --}}
@@php($ctaHeading = cms_constant('home', 'cta', 'heading'))
@@if ($ctaHeading) &lt;h2&gt;@{{ $ctaHeading }}&lt;/h2&gt; @@endif</code></pre>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">3. API access</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        This section's constants are exposed under
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">data.cms[...].constant</code>
                        as a flat key &rarr; value map.
                    </p>
                </div>

                <p class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                    <span>
                        File-type constants store a media asset and resolve to its public URL. Changes apply everywhere the
                        constant is read.
                    </span>
                </p>
            </div>

            <div class="flex items-center justify-end border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <flux:button variant="primary" size="sm" @click="showConstantGuide = false">Got it</flux:button>
            </div>
        </div>
    </div>

</div>
