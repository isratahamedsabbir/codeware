{{-- A single root element wraps the whole file (Livewire requires exactly
     one) — the page heading below and the card after it used to be two
     top-level sibling divs, which meant only one of them was actually
     inside Livewire's tracked root and the other silently stopped updating
     after the first render. --}}
<div>

    {{-- Page heading is rendered here (layouts.admin's own is disabled via
         hidePageHeading in Index::render()) rather than pushed into
         @stack('page-header-actions') like every other admin index page: that
         stack is flushed into the surrounding layout on the initial full-page
         load only, so content in it that depends on reactive Livewire state
         ($selectedIds) never updates again after a wire:click round trip. Being
         part of the component's own re-rendered template, this does. --}}
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.pages'])
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if (count($selectedIds) > 0)
                {{-- Not wrapped in .page-header-actions (see below) — that class
                     forces every button inside it to the solid blue "primary
                     action" look (resources/css/app.css), which would swallow
                     Delete's red/danger and Export's outline styling. --}}
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
                <flux:button variant="outline" size="sm" icon="arrow-down-tray"
                    href="{{ route('admin.pages.export', ['ids' => $selectedIds]) }}">
                    Export ({{ count($selectedIds) }})
                </flux:button>
            @endif
            {{-- .page-header-actions restores the solid blue "primary action"
                 look these buttons had when they lived in @push('page-header-actions')
                 (see resources/css/app.css). --}}
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:modal.trigger name="editor-settings">
                    <flux:button variant="ghost" size="sm" icon="cog-6-tooth">
                        Settings
                    </flux:button>
                </flux:modal.trigger>

                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="window" icon-trailing="chevron-down">
                        Layout
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item :href="$this->getLayoutEditorUrl('header')" target="_blank" icon="window">
                            Edit Header
                        </flux:menu.item>
                        <flux:menu.item :href="$this->getLayoutEditorUrl('footer')" target="_blank" icon="bars-3-bottom-left">
                            Edit Footer
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.pages.create') }}" wire:navigate>
                    New page
                </flux:button>
            </div>
        </div>
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4">
        <x-per-page-select :options="$this->perPageOptions()" />

        {{-- Type filter --}}
        <select wire:model.live="typeFilter"
            class="text-sm border border-zinc-200 rounded-lg px-3 py-2 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all">
            <option value="all">All types</option>
            @foreach (\App\Livewire\Admin\Pages\Index::TYPES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Search --}}
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search pages…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table with Sortable --}}
    <div class="overflow-x-auto"
        x-data="{
            init() {
                if (typeof Sortable === 'undefined') return;
                new Sortable(this.$refs.sortableRows, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: (evt) => {
                        const rows = [...this.$refs.sortableRows.querySelectorAll('[data-page-id]')];
                        const order = rows.map(r => parseInt(r.dataset.pageId));
                        $wire.reorder(order);
                    }
                });
            }
        }">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:20%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col style="width:9%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col class="hidden lg:table-column" style="width:14%">
                    <col style="width:16%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Title</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Template</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Created by</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableRows" class="divide-y divide-gray-200">
                    @forelse ($pages as $page)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors {{ in_array($page->id, $selectedIds, true) ? 'bg-indigo-50/50' : '' }}"
                            data-page-id="{{ $page->id }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $page->id }}) }">

                            {{-- Expand toggle, small screens only, where columns are hidden. --}}
                            <td class="px-1 py-2 text-center">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $page->id"
                                        wire:click="{{ $viewingId === $page->id ? 'closeDetails' : 'viewDetails('.$page->id.')' }}" />
                                </div>
                            </td>

                            {{-- Drag handle / select checkbox. Dragging is meaningless once a bulk
                                 selection is active (rows are picked, not reordered), so the drag
                                 handle hides and a checkbox takes its place instead. --}}
                            <td class="px-2 py-2 text-center">
                                <div @click.stop>
                                    @if (count($selectedIds) > 0)
                                        <input type="checkbox" wire:click="toggleSelect({{ $page->id }})"
                                            @checked(in_array($page->id, $selectedIds, true))
                                            class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                    @else
                                        <div class="drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 inline-flex">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="3" y1="6" x2="21" y2="6" />
                                                <line x1="3" y1="12" x2="21" y2="12" />
                                                <line x1="3" y1="18" x2="21" y2="18" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- ID --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <x-copy-text :text="$page->id" class="font-mono text-sm text-zinc-500">{{ $page->id }}</x-copy-text>
                            </td>

                            {{-- Title + slug (slug below, click to copy) --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug"
                                    @if ($page->getTranslation('title', 'bn', false))
                                        title="{{ $page->getTranslation('title', 'en', false) }} — {{ $page->getTranslation('title', 'bn', false) }}"
                                    @endif>
                                    <x-truncate :text="$page->getTranslation('title', 'en', false)" />
                                </div>
                                <div class="mt-0.5">
                                    <x-copy-text :text="$page->slug" class="font-mono text-[11px] text-zinc-500 block">
                                        <x-truncate :text="$page->slug" />
                                    </x-copy-text>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                @php
                                    $typeColors = [
                                        'page' => 'bg-zinc-100 text-zinc-600 border-zinc-200',
                                        'post' => 'bg-purple-50 text-purple-600 border-purple-200',
                                        'product' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                                        'product_category' => 'bg-amber-50 text-amber-600 border-amber-200',
                                        'post_category' => 'bg-sky-50 text-sky-600 border-sky-200',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $typeColors[$page->type] ?? $typeColors['page'] }}">
                                    {{ \App\Livewire\Admin\Pages\Index::TYPES[$page->type] ?? $page->type }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($page->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $page->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $page->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Template --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-600">{{ $page->template }}</span>
                            </td>

                            {{-- Created by --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-500">
                                {{ $page->creator?->name ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                @php $bulkActive = count($selectedIds) > 0; @endphp
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.pages.edit', $page->id), 'icon' => 'pencil', 'label' => 'Constant', 'color' => 'primary', 'disabled' => $bulkActive],
                                    ['wireClick' => 'openPuckEditor(' . $page->id . ')', 'icon' => 'squares', 'label' => 'Layout', 'color' => 'secondary', 'disabled' => $bulkActive],
                                    ['href' => route('admin.cms', ['pageId' => $page->id]), 'icon' => 'grid-cross', 'label' => 'CMS', 'color' => 'emerald-500', 'visible' => \App\Support\Features::enabled('cms'), 'disabled' => $bulkActive],
                                    ['wireClick' => 'confirmDelete(' . $page->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $page->id)
                            <x-admin-row-details colspan="8">
                                <x-admin-row-details.item label="Type">{{ \App\Livewire\Admin\Pages\Index::TYPES[$page->type] ?? $page->type }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Template">{{ $page->template }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Created by">{{ $page->creator?->name ?? '—' }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg>
                                <p class="text-sm text-zinc-600">No pages found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $pages->links() }}
    </div>

    {{-- Editor Settings Modal --}}
    <flux:modal name="editor-settings" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'editor-settings') $flux.modal('editor-settings').show()"
        x-on:close-modal.window="if ($event.detail.name === 'editor-settings') $flux.modal('editor-settings').close()">
        <div class="space-y-4">
            <flux:heading>Editor settings</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                How long a Puck editor link stays valid after you open it. Past this time, the tab must be reopened
                from here to get a fresh link.
            </flux:text>
            <flux:field>
                <flux:label>Token expiry (minutes)</flux:label>
                <flux:input type="number" min="1" max="1440" wire:model="puckSessionMinutes" />
                <flux:error name="puckSessionMinutes" />
            </flux:field>
            <div class="flex gap-2 pt-1">
                <flux:button size="sm" variant="primary" wire:click="saveEditorSettings">Save</flux:button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal name="page-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'page-delete') $flux.modal('page-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'page-delete') $flux.modal('page-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete page?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The page will be soft-deleted.
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    Delete
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk Delete Modal --}}
    <flux:modal name="page-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'page-bulk-delete') $flux.modal('page-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'page-bulk-delete') $flux.modal('page-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ Str::plural('page', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The selected pages will be soft-deleted.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="bulkDelete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    Delete
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>

</div>
