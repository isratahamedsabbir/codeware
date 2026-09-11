@push('page-header-actions')
    <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.product-attributes.create') }}" wire:navigate>
        New attribute
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
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search attributes…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:8%">
                    <col style="width:27%">
                    <col style="width:45%">
                    <col style="width:20%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Values</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($productAttributes as $attribute)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors" @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">

                            {{-- Id --}}
                            <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                {{ $attribute->id }}
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
                                    <x-truncate :text="$attribute->name" />
                                </div>
                            </td>

                            {{-- Values --}}
                            <td class="px-4 py-2">
                                @if (empty($attribute->values))
                                    <span class="text-zinc-300 text-sm">—</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (array_slice($attribute->values, 0, 4) as $value)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-violet-50 text-violet-600 border border-violet-200">
                                                {{ $value }}
                                            </span>
                                        @endforeach
                                        @if (count($attribute->values) > 4)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-zinc-100 text-zinc-500">
                                                +{{ count($attribute->values) - 4 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.product-attributes.edit', $attribute->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                    ['wireClick' => 'confirmDelete(' . $attribute->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                ]" />
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                </svg>
                                <p class="text-sm text-zinc-600">No attributes found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $productAttributes->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="product-attribute-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'product-attribute-delete') $flux.modal('product-attribute-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'product-attribute-delete') $flux.modal('product-attribute-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete attribute?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                This action cannot be undone. Products already using this attribute's name in their Variations keep it as plain text — it just won't be selectable here anymore.
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
