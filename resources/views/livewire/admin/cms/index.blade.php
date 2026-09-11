@push('page-header-actions')
    <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.cms.create', ['pageId' => $page->id]) }}" wire:navigate>
        New section
    </flux:button>
@endpush

@push('page-header-actions')
    <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.pages') }}" wire:navigate>
        Back
    </flux:button>
@endpush

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4">
        <x-per-page-select :options="$this->perPageOptions()" />
        {{-- Search --}}
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto"
        x-data="{
            init() {
                if (typeof Sortable === 'undefined') return;
                new Sortable(this.$refs.sortableRows, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: (evt) => {
                        const rows = [...this.$refs.sortableRows.querySelectorAll('[data-cms-id]')];
                        const order = rows.map(r => parseInt(r.dataset.cmsId));
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
                    <col style="width:26%">
                    <col style="width:14%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Content</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableRows" class="divide-y divide-gray-200">
                    @forelse ($sections as $cms)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors" data-cms-id="{{ $cms->id }}" @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">

                            {{-- Expand toggle (small screens only, where columns are hidden) --}}
                            <td class="px-2 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $cms->id"
                                    wire:click="{{ $viewingId === $cms->id ? 'closeDetails' : 'viewDetails('.$cms->id.')' }}" />
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
                                <span class="text-sm text-zinc-500 font-mono">{{ $cms->id }}</span>
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <span class="text-sm font-medium text-zinc-800"><x-truncate :text="$cms->name" /></span>
                            </td>

                            {{-- Content summary --}}
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap gap-1.5 text-[11px]">
                                    <span class="px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-600">{{ count($cms->cards ?? []) }} card{{ count($cms->cards ?? []) === 1 ? '' : 's' }}</span>
                                    @if (filled($cms->constantMap()))
                                        <span class="px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-600">{{ count($cms->constantMap()) }} constant</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($cms->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $cms->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $cms->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.cms.edit', ['pageId' => $page->id, 'id' => $cms->id]), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                    ['wireClick' => 'confirmDelete(' . $cms->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $cms->id)
                            <x-admin-row-details colspan="6">
                                @if (filled($cms->constantMap()))
                                    <x-admin-row-details.item label="Constants">
                                        <div class="text-left">
                                            @foreach ($cms->constantMap() as $key => $value)
                                                <div>{{ $key }}: {{ $value }}</div>
                                            @endforeach
                                        </div>
                                    </x-admin-row-details.item>
                                @endif
                                @foreach ($cms->localizedCards() as $card)
                                    <x-admin-row-details.item label="Card {{ $loop->iteration }}">
                                        <div class="flex items-center gap-2 justify-end">
                                            @if ($card['image'])
                                                <img src="{{ $card['image'] }}" alt="" class="w-8 h-8 rounded-lg object-cover border border-zinc-100 shrink-0">
                                            @endif
                                            <div class="text-left">
                                                <div class="font-medium">{{ $card['title'] ?: '—' }}</div>
                                                @if ($card['description'])
                                                    <div class="text-xs text-zinc-500">{{ $card['description'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </x-admin-row-details.item>
                                @endforeach
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="7" height="7" />
                                    <rect x="14" y="3" width="7" height="7" />
                                    <rect x="14" y="14" width="7" height="7" />
                                    <rect x="3" y="14" width="7" height="7" />
                                </svg>
                                <p class="text-sm text-zinc-600">No CMS sections found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $sections->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="cms-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'cms-delete') $flux.modal('cms-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'cms-delete') $flux.modal('cms-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete CMS section?</flux:heading>
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

</div>
