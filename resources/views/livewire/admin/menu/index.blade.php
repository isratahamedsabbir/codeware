<div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-zinc-100 flex-wrap">
        <p class="text-sm text-zinc-500">
            {{ __('Drag to reorder, toggle items on or off, or add a new group or link. Changes apply to the sidebar immediately.') }}
        </p>
        <button wire:click="openCreate()"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white transition-colors"
            style="background:#22c55e" onmouseover="this.style.background='#16a34a'"
            onmouseout="this.style.background='#22c55e'">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            {{ __('New menu item') }}
        </button>
    </div>

    {{-- Sortable tree --}}
    <div class="px-6 py-4"
        x-data="{
            initTop() {
                if (typeof Sortable === 'undefined') return;
                new Sortable(this.$refs.topSortable, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: () => {
                        const rows = [...this.$refs.topSortable.querySelectorAll(':scope > [data-item-id]')];
                        $wire.reorderTopLevel(rows.map(r => parseInt(r.dataset.itemId)));
                    },
                });
            },
            initGroup(el, groupId) {
                if (typeof Sortable === 'undefined') return;
                new Sortable(el, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: () => {
                        const rows = [...el.querySelectorAll(':scope > [data-item-id]')];
                        $wire.reorderChildren(groupId, rows.map(r => parseInt(r.dataset.itemId)));
                    },
                });
            },
        }"
        x-init="initTop()">
        <div class="border border-zinc-100 rounded-lg divide-y divide-zinc-50" x-ref="topSortable">
            @forelse ($topLevel as $item)
                <div data-item-id="{{ $item->id }}" wire:key="top-{{ $item->id }}">

                    {{-- Row --}}
                    <div class="flex items-center gap-3 px-4 py-3 {{ $item->is_active ? '' : 'opacity-50' }}">
                        <div class="drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <line x1="3" y1="12" x2="21" y2="12" />
                                <line x1="3" y1="18" x2="21" y2="18" />
                            </svg>
                        </div>

                        <div class="w-6 shrink-0 text-zinc-400">
                            @php $iconName = \App\Models\AdminMenuItem::iconExists($item->icon) ? $item->icon : 'link'; @endphp
                            @if ($item->is_group)
                                <flux:icon.folder class="size-4.5" />
                            @else
                                <x-dynamic-component :component="'flux::icon.'.$iconName" class="size-4.5" />
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-zinc-800">{{ __($item->label) }}</span>
                            @if ($item->is_group)
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-600 align-middle">
                                    {{ __('Group') }}
                                </span>
                            @elseif ($item->route_name)
                                <span class="ml-1.5 font-mono text-[11px] text-zinc-400">{{ $item->route_name }}</span>
                            @elseif ($item->url)
                                <span class="ml-1.5 font-mono text-[11px] text-zinc-400">{{ $item->url }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">

                            @if ($item->is_group)
                                <button wire:click="openCreate({{ $item->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 bg-white hover:bg-zinc-50 transition-colors">
                                    <flux:icon.plus class="size-3.5" />
                                    {{ __('Add item') }}
                                </button>
                            @endif

                            {{-- Toggle active --}}
                            <div class="relative group">
                                <button wire:click="toggleActive({{ $item->id }})"
                                    aria-label="{{ $item->is_active ? __('Deactivate') : __('Activate') }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-zinc-50 text-zinc-500 hover:bg-zinc-600 hover:text-white hover:-translate-y-px">
                                    @if ($item->is_active)
                                        <flux:icon.eye-slash class="w-3.5 h-3.5" />
                                    @else
                                        <flux:icon.eye class="w-3.5 h-3.5" />
                                    @endif
                                </button>
                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-zinc-600 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    {{ $item->is_active ? __('Deactivate') : __('Activate') }}
                                </span>
                            </div>

                            {{-- Edit --}}
                            <div class="relative group">
                                <button wire:click="edit({{ $item->id }})" aria-label="{{ __('Edit') }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white hover:-translate-y-px">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-indigo-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    {{ __('Edit') }}
                                </span>
                            </div>

                            {{-- Delete --}}
                            <div class="relative group">
                                <button wire:click="confirmDelete({{ $item->id }})" aria-label="{{ __('Delete') }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white hover:-translate-y-px">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M9 6V4h6v2" />
                                    </svg>
                                </button>
                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-rose-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    {{ __('Delete') }}
                                </span>
                            </div>

                        </div>
                    </div>

                    {{-- Children --}}
                    @if ($item->is_group)
                        <div class="pl-11 pr-4 pb-3 space-y-1" x-init="initGroup($el, {{ $item->id }})">
                            @forelse ($item->children as $child)
                                <div data-item-id="{{ $child->id }}" wire:key="child-{{ $child->id }}"
                                    class="flex items-center gap-3 px-3 py-2 rounded-lg bg-zinc-50/60 {{ $child->is_active ? '' : 'opacity-50' }}">
                                    <div class="drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 shrink-0">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="3" y1="6" x2="21" y2="6" />
                                            <line x1="3" y1="12" x2="21" y2="12" />
                                            <line x1="3" y1="18" x2="21" y2="18" />
                                        </svg>
                                    </div>
                                    @php $childIcon = \App\Models\AdminMenuItem::iconExists($child->icon) ? $child->icon : 'link'; @endphp
                                    <x-dynamic-component :component="'flux::icon.'.$childIcon" class="size-4 text-zinc-400 shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm text-zinc-700">{{ __($child->label) }}</span>
                                        @if ($child->route_name)
                                            <span class="ml-1.5 font-mono text-[11px] text-zinc-400">{{ $child->route_name }}</span>
                                        @elseif ($child->url)
                                            <span class="ml-1.5 font-mono text-[11px] text-zinc-400">{{ $child->url }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button wire:click="toggleActive({{ $child->id }})"
                                            aria-label="{{ $child->is_active ? __('Deactivate') : __('Activate') }}"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150 bg-white text-zinc-500 hover:bg-zinc-600 hover:text-white">
                                            @if ($child->is_active)
                                                <flux:icon.eye-slash class="w-3 h-3" />
                                            @else
                                                <flux:icon.eye class="w-3 h-3" />
                                            @endif
                                        </button>
                                        <button wire:click="edit({{ $child->id }})" aria-label="{{ __('Edit') }}"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150 bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $child->id }})" aria-label="{{ __('Delete') }}"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                                <path d="M9 6V4h6v2" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-zinc-400 px-3 py-2">{{ __('No items in this group yet.') }}</p>
                            @endforelse
                        </div>
                    @endif

                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <flux:icon.bars-3 class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                    <p class="text-sm text-zinc-600">{{ __('No menu items found.') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal name="menu-item-form" class="md:w-md"
        x-on:open-modal.window="if ($event.detail.name === 'menu-item-form') $flux.modal('menu-item-form').show()"
        x-on:close-modal.window="if ($event.detail.name === 'menu-item-form') $flux.modal('menu-item-form').close()">
        <div class="space-y-4">
            <flux:heading>{{ $editingId ? __('Edit menu item') : __('New menu item') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Label') }}</flux:label>
                <flux:input wire:model="label" placeholder="{{ __('e.g. Reports') }}" />
                <flux:error name="label" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Icon') }}</flux:label>
                <div class="flex items-center gap-2">
                    <flux:input wire:model.live="icon" placeholder="home" class="font-mono" />
                    <div class="w-9 h-9 shrink-0 rounded-lg border border-zinc-200 flex items-center justify-center text-zinc-500">
                        @if (\App\Models\AdminMenuItem::iconExists($icon))
                            <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4.5" />
                        @else
                            <flux:icon.question-mark-circle class="size-4.5 text-zinc-300" />
                        @endif
                    </div>
                </div>
                <flux:description>{{ __('A Flux icon name, e.g. home, cube, users.') }}</flux:description>
                <flux:error name="icon" />
            </flux:field>

            @unless ($editingId || $parent_id)
                <flux:field variant="inline">
                    <flux:switch wire:model.live="is_group" />
                    <flux:label>{{ __('This is a group (holds other items)') }}</flux:label>
                </flux:field>
            @endunless

            @unless ($is_group)
                <flux:field>
                    <flux:label>{{ __('Link') }}</flux:label>
                    <flux:input wire:model="url" placeholder="/admin/users or https://example.com" />
                    <flux:description>{{ __('A path (e.g. /admin/users) or a full URL.') }}</flux:description>
                    <flux:error name="url" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Group') }}</flux:label>
                    <flux:select wire:model="parent_id">
                        <flux:select.option value="">{{ __('— Top level —') }}</flux:select.option>
                        @foreach ($groups as $group)
                            <flux:select.option value="{{ $group->id }}">{{ __($group->label) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="parent_id" />
                </flux:field>
            @endunless

            <div class="flex gap-2 pt-1">
                <button wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
                    style="background:#28A745">
                    {{ $editingId ? __('Update') : __('Create') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal name="menu-item-delete" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'menu-item-delete') $flux.modal('menu-item-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'menu-item-delete') $flux.modal('menu-item-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>{{ __('Delete menu item?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This action cannot be undone. Groups with items inside cannot be deleted until those items are moved or removed.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Delete') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
