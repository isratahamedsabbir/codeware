<div class="max-w-[1600px] w-full mx-auto flex-1" x-data="{ showConstantGuide: false }">

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
            description="Freeform key/value pairs — custom flags or extra content." collapsible :collapsed="true">
            <x-slot:titleActions>
                <button type="button" @click="showConstantGuide = true" title="How page constants work"
                    class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                    <flux:icon.information-circle class="size-4" />
                </button>
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

    {{-- ── Page Constant guide modal ── --}}
    <div x-show="showConstantGuide" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @keydown.escape.window="showConstantGuide = false">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
            @click.away="showConstantGuide = false">

            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                <h3 class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    <flux:icon.variable class="size-4 text-primary" />
                    How Page Constants Work
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
                    A <strong>page constant</strong> is a key/value pair attached to <em>this</em> page only —
                    handy for custom flags, labels or extra content unique to it. It is separate from the
                    site-wide constants (Settings &rarr; Constant).
                </p>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">1. Give it a unique key</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Keys must be letters, numbers and underscores only — e.g.
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">og_type</code>.
                        Set the value and save the page.
                    </p>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">2. Read it by page slug + key</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Use the helper from any theme template or view, passing the page slug:</p>
                    <pre class="mt-2 overflow-x-auto rounded-md bg-zinc-900 p-3 font-mono text-[11px] leading-relaxed text-zinc-100"><code>@{{-- e.g. the page's slug is 'about' --}}
@{{ page_constant('about', 'og_type') }}

@@if (page_constant('about', 'og_type') === 'website')
    @{{-- custom behavior just for this page --}}
@@endif</code></pre>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">3. API access</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        The public API exposes this page's constants under
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">data.constant</code>
                        as a flat key &rarr; value map. CMS sections carry their own
                        <code class="rounded bg-zinc-100 px-1 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">constant</code>
                        map too.
                    </p>
                </div>

                <p class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                    <span>
                        Constants are saved with the page — templates/pages share state, so key changes affect every
                        place that reads them. File-type constants store a media asset and resolve to its public URL.
                    </span>
                </p>
            </div>

            <div class="flex items-center justify-end border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <flux:button variant="primary" size="sm" @click="showConstantGuide = false">Got it</flux:button>
            </div>
        </div>
    </div>

</div>
