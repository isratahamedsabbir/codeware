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
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.product-attributes'])
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
                    href="{{ route('admin.product-attributes.export', ['ids' => $selectedIds]) }}">
                    Export ({{ count($selectedIds) }})
                </flux:button>
            @endif
            {{-- .page-header-actions restores the solid blue "primary action"
                 look this button had when it lived in @push('page-header-actions')
                 (see resources/css/app.css). --}}
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.product-attributes.create') }}" wire:navigate>
                    New attribute
                </flux:button>
            </div>
        </div>
    </div>

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
                    <col style="width:6%">
                    <col style="width:22%">
                    <col style="width:35%">
                    <col style="width:17%">
                    <col style="width:20%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Values</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Created by</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($productAttributes as $attribute)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($attribute->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $attribute->id }}) }">

                            {{-- Select + Id. The checkbox only appears once a bulk selection is
                                 already active (started via Ctrl/Cmd+click on a row) — it stays
                                 hidden otherwise so the row looks normal. --}}
                            <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                <div class="flex items-center justify-center gap-1.5" @click.stop>
                                    @if (count($selectedIds) > 0)
                                        <input type="checkbox" wire:click="toggleSelect({{ $attribute->id }})"
                                            @checked(in_array($attribute->id, $selectedIds, true))
                                            class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                    @endif
                                    <x-copy-text :text="$attribute->id" class="text-xs text-zinc-500">{{ $attribute->id }}</x-copy-text>
                                </div>
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

                            {{-- Created by --}}
                            <td class="px-4 py-2 text-sm text-zinc-500">
                                {{ $attribute->creator?->name ?? '—' }}
                            </td>

                            {{-- Actions — disabled while a bulk selection is active, so the
                                 per-row actions can't conflict with the bulk toolbar above. --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                @php $bulkActive = count($selectedIds) > 0; @endphp
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.product-attributes.edit', $attribute->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => $bulkActive],
                                    ['wireClick' => 'confirmDelete(' . $attribute->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive],
                                ]" />
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
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

    {{-- Bulk Delete Modal --}}
    <flux:modal name="product-attribute-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'product-attribute-bulk-delete') $flux.modal('product-attribute-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'product-attribute-bulk-delete') $flux.modal('product-attribute-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('attribute', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                This action cannot be undone. Products already using these attributes' names in their Variations keep them as plain text — they just won't be selectable here anymore.
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
