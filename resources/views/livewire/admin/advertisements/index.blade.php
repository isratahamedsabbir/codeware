<div>

    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.advertisements'])
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if (count($selectedIds) > 0)
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
            @endif
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.advertisements.create') }}" wire:navigate>
                    New advertisement
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
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or code…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:13%">
                    <col style="width:5%">
                    <col style="width:24%">
                    <col style="width:7%">
                    <col style="width:20%">
                    <col style="width:12%">
                    <col style="width:14%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">Code</th>
                        <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Image</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Clicks</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Validity</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($advertisements as $ad)
                        <tr wire:key="advertisement-{{ $ad->id }}"
                            class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($ad->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $ad->id }}) }">

                            {{-- Bulk-select checkbox --}}
                            <td class="px-2 py-2 text-center" @click.stop>
                                @if (count($selectedIds) > 0)
                                    <input type="checkbox" wire:click="toggleSelect({{ $ad->id }})"
                                        @checked(in_array($ad->id, $selectedIds, true))
                                        class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                @endif
                            </td>

                            {{-- Code --}}
                            <td class="px-2 py-2 text-center">
                                <x-copy-text :text="$ad->code" class="text-[11px] font-mono text-zinc-500">{{ $ad->code }}</x-copy-text>
                            </td>

                            {{-- Image --}}
                            <td class="px-4 py-2 text-center">
                                @if ($ad->image)
                                    <img src="{{ $ad->image }}" alt="" class="mx-auto h-9 w-9 rounded-md object-cover border border-zinc-200" />
                                @else
                                    <span class="mx-auto flex h-9 w-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-300">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                            <path d="m21 15-5-5L5 21" />
                                        </svg>
                                    </span>
                                @endif
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
                                    <x-truncate :text="$ad->name" />
                                </div>
                                @if ($ad->url)
                                    <div class="text-xs text-zinc-400 mt-0.5 truncate">
                                        <x-truncate :text="$ad->url" />
                                    </div>
                                @endif
                            </td>

                            {{-- Clicks --}}
                            <td class="px-4 py-2 text-center">
                                <span class="font-mono text-sm tabular-nums text-zinc-700">{{ number_format($ad->clicks) }}</span>
                            </td>

                            {{-- Validity --}}
                            <td class="px-4 py-2 text-xs text-zinc-600">
                                @if ($ad->valid_from && $ad->valid_until)
                                    {{ $ad->valid_from->format('M j, Y') }} → {{ $ad->valid_until->format('M j, Y') }}
                                @elseif ($ad->valid_from)
                                    {{ __('From') }} {{ $ad->valid_from->format('M j, Y') }}
                                @elseif ($ad->valid_until)
                                    {{ __('Until') }} {{ $ad->valid_until->format('M j, Y') }}
                                @else
                                    <span class="text-zinc-400">{{ __('Always on') }}</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @php
                                    $state = $ad->valid_from && $ad->valid_from->isFuture() ? 'scheduled'
                                        : ($ad->valid_until && $ad->valid_until->isPast() ? 'expired' : 'active');
                                @endphp
                                @if ($state === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        {{ __('Active') }}
                                    </span>
                                @elseif ($state === 'scheduled')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ __('Scheduled') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 border border-zinc-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                        {{ __('Expired') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                @php $bulkActive = count($selectedIds) > 0; @endphp
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.advertisements.edit', $ad->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => $bulkActive],
                                    ['wireClick' => 'confirmDelete(' . $ad->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive],
                                ]" />
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <path d="M9 3v18M15 3v18" />
                                </svg>
                                <p class="text-sm text-zinc-600">No advertisements found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $advertisements->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="advertisement-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'advertisement-delete') $flux.modal('advertisement-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'advertisement-delete') $flux.modal('advertisement-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete advertisement?</flux:heading>
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
    <flux:modal name="advertisement-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'advertisement-bulk-delete') $flux.modal('advertisement-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'advertisement-bulk-delete') $flux.modal('advertisement-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('advertisement', count($selectedIds)) }}?</flux:heading>
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