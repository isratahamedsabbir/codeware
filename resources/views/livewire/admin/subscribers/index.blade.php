{{-- A single root element wraps the whole file (Livewire requires exactly
     one) — the page heading below and the card after it used to be two
     top-level sibling divs, which meant only one of them was actually
     inside Livewire's tracked root and the other silently stopped updating
     after the first render. --}}
<div>

    {{-- Page heading is rendered here (layouts.admin's own is disabled via
         hidePageHeading in Index::render()) rather than the normal
         @stack('page-header-actions') flow (this page never used that stack —
         subscribers are only ever added by public site signup, never from the
         admin) because the bulk-action buttons below depend on reactive
         Livewire state ($selectedIds), which a @push('page-header-actions')
         block would never update again after the first wire:click round trip. --}}
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.subscribers'])
        </div>
        @if (count($selectedIds) > 0)
            <div class="flex items-center gap-2 shrink-0">
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
                <flux:button variant="outline" size="sm" icon="arrow-down-tray"
                    href="{{ route('admin.subscribers.export', ['ids' => $selectedIds]) }}">
                    Export ({{ count($selectedIds) }})
                </flux:button>
            </div>
        @endif
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="subscribed">Subscribed</option>
            <option value="unsubscribed">Unsubscribed</option>
        </select>
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by email…"
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
                    <col class="hidden lg:table-column" style="width:8%">
                    <col style="width:39%">
                    <col style="width:20%">
                    <col class="hidden lg:table-column" style="width:15%">
                    <col style="width:8%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Date</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($subscribers as $subscriber)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($subscriber->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $subscriber->id }}) }">

                            {{-- Expand toggle, small screens only, where columns are hidden. --}}
                            <td class="px-1 py-2 text-center">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $subscriber->id"
                                        wire:click="{{ $viewingId === $subscriber->id ? 'closeDetails' : 'viewDetails('.$subscriber->id.')' }}" />
                                </div>
                            </td>

                            {{-- Bulk-select checkbox — its own dedicated column so it never
                                 crowds into neighboring cells; stays hidden until a selection
                                 is already in progress. --}}
                            <td class="px-1 py-2 text-center">
                                <div @click.stop>
                                    @if (count($selectedIds) > 0)
                                        <input type="checkbox" wire:click="toggleSelect({{ $subscriber->id }})"
                                            @checked(in_array($subscriber->id, $selectedIds, true))
                                            class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                    @endif
                                </div>
                            </td>

                            {{-- Id --}}
                            <td class="hidden lg:table-cell px-2 py-2 text-center text-xs text-zinc-500">
                                <x-copy-text :text="$subscriber->id" class="text-xs text-zinc-500">{{ $subscriber->id }}</x-copy-text>
                            </td>

                            {{-- Email --}}
                            <td class="px-4 py-2">
                                <span class="font-medium text-zinc-900 text-sm"><x-truncate :text="$subscriber->email" /></span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($subscriber->status === 'subscribed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Subscribed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Unsubscribed
                                    </span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-500 whitespace-nowrap">{{ $subscriber->created_at->toDisplay() }}</span>
                            </td>

                            {{-- Actions — disabled while a bulk selection is active, so the
                                 per-row actions can't conflict with the bulk toolbar above. --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['wireClick' => 'confirmDelete(' . $subscriber->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => count($selectedIds) > 0],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $subscriber->id)
                            <x-admin-row-details colspan="7">
                                <x-admin-row-details.item label="ID">#{{ $subscriber->id }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Date">{{ $subscriber->created_at->toDisplay() }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                                </svg>
                                <p class="text-sm text-zinc-600">No subscribers found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $subscribers->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="subscriber-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'subscriber-delete') $flux.modal('subscriber-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'subscriber-delete') $flux.modal('subscriber-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete subscriber?</flux:heading>
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
    <flux:modal name="subscriber-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'subscriber-bulk-delete') $flux.modal('subscriber-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'subscriber-bulk-delete') $flux.modal('subscriber-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('subscriber', count($selectedIds)) }}?</flux:heading>
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
