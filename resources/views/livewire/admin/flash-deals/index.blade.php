<div>

    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials._admin-breadcrumbs', ['routeName' => 'admin.flash-deals'])
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if (count($selectedIds) > 0)
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
            @endif
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.flash-deals.create') }}" wire:navigate>
                    New flash deal
                </flux:button>
            </div>
        </div>
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white pr-8 min-w-[140px] transition-all">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search flash deals…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Deal</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Products</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Window</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">State</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($deals as $deal)
                        <tr wire:key="flash-deal-{{ $deal->id }}"
                            class="group/row hover:bg-indigo-50/30 transition-colors {{ in_array($deal->id, $selectedIds, true) ? 'bg-indigo-50/50' : '' }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $deal->id }}) }">
                            <td class="px-2 py-2 text-center text-xs text-zinc-500">{{ $deal->id }}</td>
                            <td class="px-4 py-2">
                                <span class="text-sm font-semibold text-zinc-900"><x-truncate :text="$deal->name" /></span>
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-700">{{ $deal->valueLabel() }}</td>
                            <td class="hidden lg:table-cell px-4 py-2 text-xs">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-200">
                                    {{ $deal->products_count }} {{ Str::plural('product', $deal->products_count) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-xs text-zinc-500">
                                {{ $deal->starts_at->toDisplay() }}<br>→ {{ $deal->ends_at->toDisplay() }}
                            </td>
                            <td class="px-4 py-2 text-xs">
                                @if ($deal->isLive())
                                    <span class="inline-flex items-center gap-1 text-green-600 font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Live now
                                    </span>
                                @elseif ($deal->ends_at->isPast())
                                    <span class="text-rose-500">Ended</span>
                                @elseif ($deal->starts_at->isFuture())
                                    <span class="text-amber-500">Upcoming</span>
                                @else
                                    <span class="text-zinc-400">Off</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if ($deal->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $deal->id }})" aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $deal->id }})" aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.flash-deals.edit', $deal->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => count($selectedIds) > 0],
                                    ['wireClick' => 'confirmDelete(' . $deal->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => count($selectedIds) > 0],
                                ]" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <p class="text-sm text-zinc-600">No flash deals found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="px-6 py-3">
        {{ $deals->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="flash-deal-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'flash-deal-delete') $flux.modal('flash-deal-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'flash-deal-delete') $flux.modal('flash-deal-delete').close()">
        <div class="space-y-4">
            <flux:heading>Delete flash deal?</flux:heading>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The products go back to their normal price.</flux:text>
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
    <flux:modal name="flash-deal-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'flash-deal-bulk-delete') $flux.modal('flash-deal-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'flash-deal-bulk-delete') $flux.modal('flash-deal-bulk-delete').close()">
        <div class="space-y-4">
            <flux:heading>Delete {{ count($selectedIds) }} {{ Str::plural('flash deal', count($selectedIds)) }}?</flux:heading>
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
