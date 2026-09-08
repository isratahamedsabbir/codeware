@push('page-header-actions')
    <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.products.create') }}" wire:navigate>
        New product
    </flux:button>
@endpush

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('admin.products.export', ['search' => $search, 'status' => $statusFilter]) }}" class="ml-auto">
            Export CSV
        </flux:button>
        {{-- Search --}}
        <div class="relative max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search products…"
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
                        const rows = [...this.$refs.sortableRows.querySelectorAll('[data-product-id]')];
                        const order = rows.map(r => parseInt(r.dataset.productId));
                        $wire.reorder(order);
                    }
                });
            }
        }">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:5%">
                    <col style="width:15%">
                    <col style="width:13%">
                    <col style="width:13%">
                    <col style="width:10%">
                    <col style="width:9%">
                    <col style="width:10%">
                    <col style="width:20%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Slug</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Featured</th>
                        <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableRows" class="divide-y divide-gray-200">
                    @forelse ($products as $product)
                        <tr class="hover:bg-indigo-50/30 transition-colors" data-product-id="{{ $product->id }}" @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">

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
                            <td class="px-4 py-2">
                                <span class="text-sm text-zinc-500 font-mono">{{ $product->id }}</span>
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
                                    <x-truncate :text="$product->getTranslation('name', 'en', false)" />
                                </div>
                                @if ($product->getTranslation('name', 'bn', false))
                                    <div class="text-xs text-zinc-600 mt-0.5">
                                        <x-truncate :text="$product->getTranslation('name', 'bn', false)" />
                                    </div>
                                @endif
                            </td>

                            {{-- Slug --}}
                            <td class="px-4 py-2">
                                <span class="font-mono text-xs text-zinc-600 truncate block">
                                    <x-truncate :text="$product->slug" />
                                </span>
                            </td>

                            {{-- Category --}}
                            <td class="px-4 py-2">
                                @if ($product->category)
                                    <span class="inline-flex w-max items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 border border-zinc-200">
                                        <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                        </svg>
                                        <x-truncate :text="$product->category->getTranslation('name', 'en', false)" />
                                    </span>
                                @else
                                    <span class="text-zinc-300 text-sm">—</span>
                                @endif
                            </td>

                            {{-- Price --}}
                            <td class="px-4 py-2">
                                <span class="text-sm text-zinc-700 font-medium">{{ number_format((float) $product->price, 2) }}</span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($product->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $product->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $product->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Featured --}}
                            <td class="px-4 py-2">
                                @if ($product->is_featured)
                                    <button type="button" wire:click="toggleFeatured({{ $product->id }})"
                                        aria-label="Unmark as featured"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 cursor-pointer hover:bg-amber-100">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                        </svg>
                                        Featured
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleFeatured({{ $product->id }})"
                                        aria-label="Mark as featured"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-50 text-zinc-400 border border-zinc-200 cursor-pointer hover:bg-zinc-100">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                        </svg>
                                        Not Featured
                                    </button>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['wireClick' => 'viewDetails(' . $product->id . ')', 'icon' => 'eye', 'label' => 'View', 'color' => 'zinc-500'],
                                    ['href' => route('admin.products.edit', $product->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                    $product->page
                                        ? ['href' => route('admin.pages.edit', $product->page->id), 'icon' => 'document', 'label' => 'Page', 'color' => 'secondary']
                                        : ['icon' => 'document', 'label' => 'Page', 'color' => 'secondary', 'disabled' => true],
                                    ['wireClick' => 'openPuckEditor(' . $product->id . ')', 'icon' => 'squares', 'label' => 'Layout', 'color' => 'secondary'],
                                    ['wireClick' => 'confirmDelete(' . $product->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                ]" />
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="9" y1="15" x2="15" y2="15" />
                                </svg>
                                <p class="text-sm text-zinc-600">No products found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $products->links() }}
    </div>

    {{-- View Modal --}}
    <flux:modal name="product-view" class="md:w-[600px]"
        x-on:open-modal.window="if ($event.detail.name === 'product-view') $flux.modal('product-view').show()">
        @if ($viewingId)
            @php $viewedProduct = \App\Models\Product::with(['category', 'page'])->find($viewingId); @endphp
            @if ($viewedProduct)
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                        <flux:heading>{{ $viewedProduct->getTranslation('name', 'en', false) }}</flux:heading>
                        <flux:modal.close>
                            <button wire:click="closeDetails" class="text-zinc-400 hover:text-zinc-600 transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </flux:modal.close>
                    </div>
                    @if ($viewedProduct->getTranslation('name', 'bn', false))
                        <div class="text-sm text-zinc-500">{{ $viewedProduct->getTranslation('name', 'bn', false) }}</div>
                    @endif
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-zinc-400">Slug:</span> <span class="text-zinc-900 font-mono">{{ $viewedProduct->slug ?: '—' }}</span></div>
                        <div><span class="text-zinc-400">Category:</span> <span class="text-zinc-900">{{ $viewedProduct->category?->getTranslation('name', 'en', false) ?: '—' }}</span></div>
                        <div><span class="text-zinc-400">Price:</span> <span class="text-zinc-900">{{ number_format((float) $viewedProduct->price, 2) }}</span></div>
                        <div><span class="text-zinc-400">Status:</span> <span class="text-zinc-900">{{ ucfirst($viewedProduct->status) }}</span></div>
                        <div><span class="text-zinc-400">Featured:</span> <span class="text-zinc-900">{{ $viewedProduct->is_featured ? 'Yes' : 'No' }}</span></div>
                        <div><span class="text-zinc-400">Created:</span> <span class="text-zinc-900">{{ $viewedProduct->created_at->toDisplay() }}</span></div>
                    </div>
                    @if ($viewedProduct->getTranslation('description', 'en', false))
                        <div class="border-t border-zinc-100 pt-3">
                            <p class="text-sm text-zinc-700 leading-relaxed">{{ $viewedProduct->getTranslation('description', 'en', false) }}</p>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal name="product-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'product-delete') $flux.modal('product-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'product-delete') $flux.modal('product-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete product?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The product will be soft-deleted.
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    Delete
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
