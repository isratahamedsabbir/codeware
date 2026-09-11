@push('page-header-actions')
    <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.product-categories.create') }}" wire:navigate>
        New category
    </flux:button>
@endpush

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
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
                    <col style="width:5%">
                    <col style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:23%">
                    <col class="hidden lg:table-column" style="width:20%">
                    <col class="hidden lg:table-column" style="width:10%">
                    <col style="width:15%">
                    <col style="width:17%">
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
                        <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableRows" class="divide-y divide-gray-200">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-indigo-50/30 transition-colors" data-category-id="{{ $category->id }}" @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">

                            {{-- Expand toggle (small screens only, where columns are hidden) --}}
                            <td class="px-2 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $category->id"
                                    wire:click="{{ $viewingId === $category->id ? 'closeDetails' : 'viewDetails('.$category->id.')' }}" />
                            </td>

                            {{-- Drag handle --}}
                            <td class="px-2 py-2 text-center">
                                <div class="drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 inline-flex">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="3" y1="6" x2="21" y2="6" />
                                        <line x1="3" y1="12" x2="21" y2="12" />
                                        <line x1="3" y1="18" x2="21" y2="18" />
                                    </svg>
                                </div>
                            </td>

                            {{-- ID --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-500 font-mono">{{ $category->id }}</span>
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
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

                            {{-- Actions --}}
                            <td class="px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.product-categories.edit', $category->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                    $category->page
                                        ? ['href' => route('admin.pages.edit', $category->page->id), 'icon' => 'document', 'label' => 'Page', 'color' => 'secondary']
                                        : ['icon' => 'document', 'label' => 'Page', 'color' => 'secondary', 'disabled' => true],
                                    ['wireClick' => 'confirmDelete(' . $category->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $category->id)
                            <x-admin-row-details colspan="7">
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
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
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

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $categories->links() }}
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

</div>
