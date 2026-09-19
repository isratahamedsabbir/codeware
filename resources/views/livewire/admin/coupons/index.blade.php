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
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.coupons'])
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
                    href="{{ route('admin.coupons.export', ['ids' => $selectedIds]) }}">
                    Export ({{ count($selectedIds) }})
                </flux:button>
            @endif
            {{-- .page-header-actions restores the solid blue "primary action"
                 look this button had when it lived in @push('page-header-actions')
                 (see resources/css/app.css). --}}
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.coupons.create') }}" wire:navigate>
                    New coupon
                </flux:button>
            </div>
        </div>
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        {{-- Status filter --}}
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        {{-- Search --}}
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search coupon code…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:8%">
                    <col style="width:7%">
                    <col style="width:8%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col style="width:8%">
                    <col class="hidden lg:table-column" style="width:11%">
                    <col style="width:7%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Discount</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Applies to</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Min order</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Usage</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Expires</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Created by</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($coupons as $coupon)
                        <tr wire:key="coupon-{{ $coupon->id }}"
                            class="group/row hover:bg-indigo-50/30 transition-colors {{ in_array($coupon->id, $selectedIds, true) ? 'bg-indigo-50/50' : '' }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $coupon->id }}) }">

                            {{-- Expand toggle, small screens only, where columns are hidden. --}}
                            <td class="px-1 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $coupon->id"
                                    wire:click="{{ $viewingId === $coupon->id ? 'closeDetails' : 'viewDetails('.$coupon->id.')' }}" />
                            </td>

                            {{-- Bulk-select checkbox — its own dedicated column so it never
                                 crowds into neighboring cells; stays hidden until a selection
                                 is already in progress. --}}
                            <td class="px-2 py-2 text-center">
                                @if (count($selectedIds) > 0)
                                    <input type="checkbox" wire:click.stop="toggleSelect({{ $coupon->id }})"
                                        @checked(in_array($coupon->id, $selectedIds, true))
                                        class="w-4 h-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                @endif
                            </td>

                            {{-- Id --}}
                            <td class="hidden lg:table-cell px-2 py-2 text-center text-xs text-zinc-500">
                                <x-copy-text :text="$coupon->id" class="text-xs text-zinc-500">{{ $coupon->id }}</x-copy-text>
                            </td>

                            {{-- Code --}}
                            <td class="px-4 py-2">
                                <span class="font-mono text-sm font-semibold text-zinc-900"><x-truncate :text="$coupon->code" /></span>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-2">
                                @if ($coupon->type === 'percentage')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 border border-indigo-200">Percentage</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600 border border-amber-200">Fixed</span>
                                @endif
                            </td>

                            {{-- Discount --}}
                            <td class="px-4 py-2 text-sm text-zinc-700">
                                @if ($coupon->type === 'percentage')
                                    {{ rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.') }}% off
                                @else
                                    {{ \App\Models\Setting::get('currency_symbol', '৳') }}{{ number_format((float) $coupon->value, 2) }} off
                                @endif
                            </td>

                            {{-- Applies to --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-xs">
                                @if ($coupon->products_count > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-200">
                                        {{ $coupon->products_count }} {{ Str::plural('product', $coupon->products_count) }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">All products</span>
                                @endif
                            </td>

                            {{-- Min order --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-500">
                                {{ $coupon->min_order_amount !== null ? \App\Models\Setting::get('currency_symbol', '৳').number_format((float) $coupon->min_order_amount, 2) : '—' }}
                            </td>

                            {{-- Usage --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-500">
                                {{ $coupon->used_count }}{{ $coupon->max_uses !== null ? ' / '.$coupon->max_uses : '' }}
                            </td>

                            {{-- Expires --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-500">
                                @if ($coupon->expires_at)
                                    <span class="{{ $coupon->isExpired() ? 'text-rose-500' : '' }}">
                                        {{ $coupon->expires_at->toDisplay() }}
                                    </span>
                                @else
                                    Never
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($coupon->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $coupon->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $coupon->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Created by --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-500">
                                {{ $coupon->creator?->name ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.coupons.edit', $coupon->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => count($selectedIds) > 0],
                                    ['wireClick' => 'confirmDelete(' . $coupon->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => count($selectedIds) > 0],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $coupon->id)
                            <x-admin-row-details colspan="13">
                                <x-admin-row-details.item label="Applies to">
                                    @if ($coupon->products_count > 0)
                                        <div class="flex flex-wrap gap-1 justify-end">
                                            @foreach ($coupon->products as $product)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    {{ $product->getTranslation('name', 'en', false) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        All products
                                    @endif
                                </x-admin-row-details.item>
                                <x-admin-row-details.item label="Min order">{{ $coupon->min_order_amount !== null ? \App\Models\Setting::get('currency_symbol', '৳').number_format((float) $coupon->min_order_amount, 2) : '—' }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Usage">{{ $coupon->used_count }}{{ $coupon->max_uses !== null ? ' / '.$coupon->max_uses : '' }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Expires">{{ $coupon->expires_at?->toDisplay() ?: 'Never' }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Created by">{{ $coupon->creator?->name ?? '—' }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="13" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M9 5H7a2 2 0 0 0-2 2v3a2 2 0 0 1-2 2 2 2 0 0 1 2 2v3a2 2 0 0 0 2 2h2m6-14h2a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2 2 2 0 0 0-2 2v3a2 2 0 0 1-2 2h-2" />
                                </svg>
                                <p class="text-sm text-zinc-600">No coupons found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $coupons->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="coupon-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'coupon-delete') $flux.modal('coupon-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'coupon-delete') $flux.modal('coupon-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete coupon?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone.</flux:text>
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
    <flux:modal name="coupon-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'coupon-bulk-delete') $flux.modal('coupon-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'coupon-bulk-delete') $flux.modal('coupon-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ Str::plural('coupon', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone.</flux:text>
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
