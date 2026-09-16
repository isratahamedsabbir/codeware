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
        @include('partials.admin-breadcrumbs', ['routeName' => 'admin.product-categories'])
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
                href="{{ route('admin.product-categories.export', ['ids' => $selectedIds]) }}">
                Export ({{ count($selectedIds) }})
            </flux:button>
        @endif
        {{-- .page-header-actions restores the solid blue "primary action"
             look this button had when it lived in @push('page-header-actions')
             (see resources/css/app.css). --}}
        <div class="page-header-actions flex items-center gap-2 shrink-0">
            <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.product-categories.create') }}" wire:navigate>
                New category
            </flux:button>
        </div>
    </div>
</div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        {{-- Search --}}
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search categories…"
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
                        const rows = [...this.$refs.sortableRows.querySelectorAll('[data-category-id]')];
                        const order = rows.map(r => parseInt(r.dataset.categoryId));
                        $wire.reorder(order);
                    }
                });
            }
        }">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col style="width:3%">
                    <col style="width:3%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:20%">
                    <col class="hidden lg:table-column" style="width:16%">
                    <col class="hidden lg:table-column" style="width:8%">
                    <col style="width:13%">
                    <col class="hidden lg:table-column" style="width:14%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Slug</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Icon</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Created by</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableRows" class="divide-y divide-gray-200">
                    @forelse ($categories as $category)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($category->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            data-category-id="{{ $category->id }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $category->id }}) }">

                            {{-- Expand toggle, small screens only, where columns are hidden. --}}
                            <td class="px-1 py-2 text-center">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $category->id"
                                        wire:click="{{ $viewingId === $category->id ? 'closeDetails' : 'viewDetails('.$category->id.')' }}" />
                                </div>
                            </td>

                            {{-- Drag handle / select checkbox. Dragging is meaningless once a bulk
                                 selection is active (rows are picked, not reordered), so the drag
                                 handle hides and a checkbox takes its place instead. --}}
                            <td class="px-1 py-2 text-center">
                                <div @click.stop>
                                    @if (count($selectedIds) > 0)
                                        <input type="checkbox" wire:click="toggleSelect({{ $category->id }})"
                                            @checked(in_array($category->id, $selectedIds, true))
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
                                <x-copy-text :text="$category->id" class="font-mono text-sm text-zinc-500">{{ $category->id }}</x-copy-text>
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2" style="padding-left: {{ 16 + ($category->depth * 24) }}px">
                                <div class="font-medium text-zinc-900 text-sm leading-snug flex items-center gap-1.5">
                                    @if ($category->depth > 0)
                                        <span class="text-zinc-300">↳</span>
                                    @endif
                                    <x-truncate :text="$category->getTranslation('name', 'en', false)" />
                                </div>
                                @if ($category->getTranslation('name', 'bn', false))
                                    <div class="text-xs text-zinc-600 mt-0.5">
                                        <x-truncate :text="$category->getTranslation('name', 'bn', false)" />
                                    </div>
                                @endif
                            </td>

                            {{-- Slug --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <x-copy-text :text="$category->slug" class="font-mono text-xs text-zinc-600 block">
                                    <x-truncate :text="$category->slug" />
                                </x-copy-text>
                            </td>

                            {{-- Icon --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                @if ($category->icon)
                                    <img src="{{ $category->icon }}" alt="Icon"
                                        class="w-8 h-8 rounded-lg object-cover border border-zinc-100" />
                                @else
                                    <img src="{{ asset('images/placeholder.svg') }}" alt="No icon"
                                        class="w-8 h-8 rounded-lg object-cover border border-zinc-100 opacity-60" />
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($category->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $category->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $category->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Created by --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-500">
                                {{ $category->creator?->name ?? '—' }}
                            </td>

                            {{-- Actions — disabled while a bulk selection is active, so the
                                 per-row actions can't conflict with the bulk toolbar above. --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                @php $bulkActive = count($selectedIds) > 0; @endphp
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.product-categories.edit', $category->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => $bulkActive],
                                    $category->page && ! $bulkActive
                                        ? ['href' => route('admin.pages.edit', $category->page->id), 'icon' => 'document', 'label' => 'Page', 'color' => 'secondary']
                                        : ['icon' => 'document', 'label' => 'Page', 'color' => 'secondary', 'disabled' => true],
                                    ['wireClick' => 'confirmDelete(' . $category->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $category->id)
                            <x-admin-row-details colspan="8">
                                <x-admin-row-details.item label="Slug">
                                    @if ($category->slug)
                                        <x-copy-text :text="$category->slug" class="font-mono">{{ $category->slug }}</x-copy-text>
                                    @else
                                        —
                                    @endif
                                </x-admin-row-details.item>
                                <x-admin-row-details.item label="Icon">
                                    @if ($category->icon)
                                        <img src="{{ $category->icon }}" alt="Icon" class="w-8 h-8 rounded-lg object-cover border border-zinc-100 ml-auto">
                                    @else
                                        —
                                    @endif
                                </x-admin-row-details.item>
                                <x-admin-row-details.item label="Created by">{{ $category->creator?->name ?? '—' }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p class="text-sm text-zinc-600">No product categories found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div> 
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="product-category-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'product-category-delete') $flux.modal('product-category-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'product-category-delete') $flux.modal('product-category-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete product category?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The category will be soft-deleted.
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
    <flux:modal name="product-category-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'product-category-bulk-delete') $flux.modal('product-category-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'product-category-bulk-delete') $flux.modal('product-category-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('category', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The selected categories will be soft-deleted.
            </flux:text>
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
