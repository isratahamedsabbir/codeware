@push('page-header-actions')
    {{-- Rendered by the layout header, outside this component's DOM root, so it
         dispatches a window event the root <div> forwards (same pattern as the
         Theme Settings "Install Theme" button). --}}
    <flux:button variant="outline" size="sm" icon="plus"
        onclick="window.dispatchEvent(new CustomEvent('open-plugin-create'))">
        New Plugin
    </flux:button>
    <flux:button variant="outline" size="sm" icon="arrow-up-tray"
        onclick="window.dispatchEvent(new CustomEvent('open-plugin-install'))">
        Install Plugin
    </flux:button>
@endpush

<div class="space-y-5"
    x-on:open-plugin-create.window="$wire.openCreateModal()"
    x-on:open-plugin-install.window="$wire.openInstallModal()">
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm dark:bg-green-950 dark:border-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @error('plugins')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm dark:bg-red-950 dark:border-red-800 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    <x-admin-section-card icon="puzzle-piece" title="Installed Plugins"
        description="Active plugins appear in the Plugins menu. Each one is managed from its own screen.">
        {{-- Sits right after the title rather than at the far end of the header:
             it is a link, not a screen of its own any more, so it reads as part of
             the heading's label — the same slot Theme Settings' "How to create a
             theme" link uses. --}}
        <x-slot:titleActions>
            <a href="{{ route('admin.developer-guide') }}#plugins" wire:navigate.hover
                title="How to build and use plugins — open the Developer Guide"
                aria-label="How to build and use plugins — open the Developer Guide"
                class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                <flux:icon.information-circle class="size-4" />
            </a>
        </x-slot:titleActions>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
            @forelse ($plugins as $slug => $plugin)
                <div class="flex items-center gap-4 py-3" wire:key="plugin-{{ $slug }}">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10">
                        <x-dynamic-component :component="'flux::icon.'.$plugin['icon']" class="size-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $plugin['name'] }}
                            <span class="text-xs font-normal text-zinc-400">v{{ $plugin['version'] }}</span>
                            @if ($plugin['default'])
                                <flux:badge size="sm" color="blue">Default</flux:badge>
                            @endif
                            @unless ($plugin['active'])
                                <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                            @endunless
                        </p>
                        @if ($plugin['description'])
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $plugin['description'] }}</p>
                        @endif
                        @if ($plugin['author'])
                            <p class="mt-0.5 text-xs text-zinc-400">By {{ $plugin['author'] }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($plugin['active'])
                            <flux:button size="sm" variant="outline" icon="arrow-top-right-on-square"
                                href="{{ route('admin.plugins.show', $slug) }}" wire:navigate>Manage</flux:button>
                        @endif

                        <flux:button size="sm" variant="ghost" icon="arrow-down-tray"
                            title="Download as zip" aria-label="Download {{ $plugin['name'] }} as zip"
                            wire:click="downloadPlugin('{{ $slug }}')"
                            wire:loading.attr="disabled" wire:target="downloadPlugin('{{ $slug }}')" />

                        @unless ($plugin['default'])
                            <flux:button size="sm" variant="{{ $plugin['active'] ? 'ghost' : 'primary' }}"
                                wire:click="toggle('{{ $slug }}')">
                                {{ $plugin['active'] ? 'Deactivate' : 'Activate' }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600"
                                wire:click="remove('{{ $slug }}')"
                                wire:confirm="Delete the plugin &quot;{{ $plugin['name'] }}&quot; and all of its files?" />
                        @endunless
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No plugins installed.</p>
            @endforelse
        </div>
    </x-admin-section-card>

    {{-- ── Create plugin modal ── --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="flex max-h-[90vh] w-full max-w-lg flex-col rounded-xl bg-white shadow-xl dark:bg-zinc-800" @click.away="$wire.closeCreateModal()">
                <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">New Plugin</h3>
                    <button wire:click="closeCreateModal" class="rounded p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>

                <div class="grid gap-4 overflow-y-auto p-6">
                    <p class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Writes <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">plugins/{slug}/</code>
                        with a <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">plugin.json</code>,
                        an <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">index.blade.php</code>
                        screen and a commented
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">routes.php</code>.
                        Edit those files, then press <b>Activate</b> on the plugin's row.
                    </p>

                    <flux:field>
                        <flux:label>Plugin name</flux:label>
                        <flux:input wire:model.live.debounce.400ms="newName" placeholder="e.g. WhatsApp Alerts" />
                        <flux:error name="newName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug (folder name)</flux:label>
                        <div class="flex items-center gap-2">
                            <flux:input wire:model.live.debounce.400ms="newSlug" class="font-mono"
                                x-on:input="$wire.set('slugEdited', true)" />
                            <span class="shrink-0 font-mono text-xs text-zinc-400">plugins/{{ $newSlug ?: '…' }}/</span>
                        </div>
                        <flux:description>{{ __('Lowercase letters, numbers, dashes and underscores — it becomes the folder name.') }}</flux:description>
                        <flux:error name="newSlug" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Version</flux:label>
                            <flux:input wire:model="newVersion" placeholder="1.0.0" />
                            <flux:error name="newVersion" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Author</flux:label>
                            <flux:input wire:model="newAuthor" placeholder="Your name" />
                            <flux:error name="newAuthor" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="newDescription" rows="2" placeholder="What the plugin does." />
                        <flux:error name="newDescription" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Icon</flux:label>
                        <div class="flex items-center gap-2">
                            <flux:input wire:model.live.debounce.400ms="newIcon" placeholder="puzzle-piece" class="font-mono" />
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 dark:border-zinc-700">
                                @if (\App\Models\MenuItem::iconExists($newIcon))
                                    <x-dynamic-component :component="'flux::icon.'.$newIcon" class="size-4.5" />
                                @else
                                    <flux:icon.question-mark-circle class="size-4.5 text-zinc-300" />
                                @endif
                            </div>
                        </div>
                        <flux:description>{{ __('A Flux icon name, e.g. bell, chat-bubble, sparkles.') }}</flux:description>
                        <flux:error name="newIcon" />
                    </flux:field>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <flux:button variant="ghost" size="sm" wire:click="closeCreateModal">Cancel</flux:button>
                    <flux:button variant="primary" size="sm" wire:click="createPlugin" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createPlugin">Create Plugin</span>
                        <span wire:loading wire:target="createPlugin">Creating…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($showInstallModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-zinc-800" @click.away="$wire.closeInstallModal()">
                <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Install Plugin</h3>
                    <button wire:click="closeInstallModal" class="rounded p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>

                <div class="grid gap-4 p-6">
                    <p class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Upload a <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">.zip</code>
                        of one plugin folder containing a
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">plugin.json</code>
                        and an
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-zinc-700">index.blade.php</code>.
                        The folder name becomes the plugin's slug.
                    </p>

                    <p class="flex items-start gap-2 text-xs text-amber-700 dark:text-amber-400">
                        <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0" />
                        <span>Plugins can run PHP on your server. Only install plugins from sources you trust.</span>
                    </p>

                    <div class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-zinc-300 bg-zinc-50/60 p-5 text-center dark:border-zinc-600 dark:bg-zinc-800/30">
                        <input type="file" wire:model="pluginZip" accept=".zip" class="hidden" id="plugin-zip-input">
                        <p class="max-w-full truncate text-xs {{ $pluginZip ? 'font-medium text-zinc-600 dark:text-zinc-300' : 'text-zinc-400' }}">
                            {{ $pluginZip ? $pluginZip->getClientOriginalName() : 'Choose a .zip file to upload' }}
                        </p>
                        <flux:button variant="outline" size="sm" onclick="document.getElementById('plugin-zip-input').click()">
                            Choose File
                        </flux:button>
                    </div>

                    @error('pluginZip')
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <flux:button variant="ghost" size="sm" wire:click="closeInstallModal">Cancel</flux:button>
                    <flux:button variant="primary" size="sm" wire:click="installPlugin" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="installPlugin">Install Plugin</span>
                        <span wire:loading wire:target="installPlugin">Installing…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
