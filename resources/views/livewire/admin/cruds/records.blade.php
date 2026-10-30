<div>
    @php
        $singular = \Illuminate\Support\Str::singular($crud->name);
        $selectClass = 'px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white min-w-[140px] transition-all';
        $hasFilters = $search !== '' || collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty();
    @endphp

    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials._admin-breadcrumbs', ['routeName' => 'admin.cruds.records', 'title' => $crud->name])
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if (count($selectedIds) > 0)
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
            @endif
            <flux:button variant="outline" size="sm" icon="arrow-down-tray"
                href="{{ route('admin.cruds.export', ['slug' => $crud->slug, 'ids' => $selectedIds]) }}">
                Export{{ count($selectedIds) > 0 ? ' ('.count($selectedIds).')' : '' }}
            </flux:button>
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:button variant="ghost" size="sm" icon="plus" wire:click="create">New {{ $singular }}</flux:button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-3 p-4 flex-wrap">
            <x-per-page-select :options="$this->perPageOptions()" />

            @foreach ($filterable as $f)
                <select wire:model.live="filters.{{ $f['name'] }}" class="{{ $selectClass }}" wire:key="filter-{{ $f['name'] }}">
                    <option value="">All — {{ $f['label'] }}</option>
                    @if ($f['type'] === 'boolean')
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    @else
                        @foreach (\App\Support\Cruds::options($f) as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    @endif
                </select>
            @endforeach

            @if ($hasFilters)
                <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
            @endif

            <span class="text-xs text-zinc-500">{{ $records->total() }} of {{ $total }} {{ \Illuminate\Support\Str::plural('record', $total) }}</span>

            <div class="relative max-w-xs ml-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search {{ strtolower($crud->name) }}…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-zinc-50">
                            @php
                                $th = 'px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider';
                                $arrow = fn ($col) => $sortBy === $col ? ($sortDir === 'asc' ? '↑' : '↓') : '';
                            @endphp
                            <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-14">
                                <button type="button" wire:click="sort('id')" class="uppercase cursor-pointer hover:text-indigo-600">#{{ $arrow('id') }}</button>
                            </th>
                            @foreach ($listFields as $f)
                                <th class="{{ $th }}">
                                    @if ($f['type'] === 'image')
                                        {{ $f['label'] }}
                                    @else
                                        <button type="button" wire:click="sort('{{ $f['name'] }}')" class="uppercase tracking-wider cursor-pointer hover:text-indigo-600">
                                            {{ $f['label'] }} {{ $arrow($f['name']) }}
                                        </button>
                                    @endif
                                </th>
                            @endforeach
                            <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($records as $record)
                            @php $selected = in_array($record->id, $selectedIds, true); @endphp
                            <tr wire:key="rec-{{ $record->id }}"
                                class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ $selected ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                                @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $record->id }}) }">
                                <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" class="rounded border-zinc-300" {{ $selected ? 'checked' : '' }}
                                            wire:click="toggleSelect({{ $record->id }})">
                                        {{ $record->id }}
                                    </label>
                                </td>
                                @foreach ($listFields as $f)
                                    @php $value = $record->getRawOriginal($f['name']); @endphp
                                    <td class="px-4 py-2 text-sm text-zinc-800">
                                        @if ($f['type'] === 'boolean')
                                            @if ($value)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Yes
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>No
                                                </span>
                                            @endif
                                        @elseif ($f['type'] === 'select' && $value)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 border border-indigo-200">{{ $value }}</span>
                                        @elseif ($f['type'] === 'image')
                                            @if ($value)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($value) }}" alt="" class="size-10 rounded-md object-cover border border-zinc-200">
                                            @else
                                                <span class="text-zinc-300">—</span>
                                            @endif
                                        @elseif ($f['type'] === 'url' && $value)
                                            <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline"><x-truncate :text="$value" /></a>
                                        @elseif ($f['type'] === 'email' && $value)
                                            <a href="mailto:{{ $value }}" class="text-indigo-600 hover:underline"><x-truncate :text="$value" /></a>
                                        @elseif ($f['type'] === 'date' && $value)
                                            {{ \Illuminate\Support\Carbon::parse($value)->format('d M Y') }}
                                        @elseif ($f['type'] === 'datetime' && $value)
                                            {{ \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i') }}
                                        @elseif ($f['type'] === 'decimal' && $value !== null)
                                            {{ number_format((float) $value, 2) }}
                                        @elseif ($value === null || $value === '')
                                            <span class="text-zinc-300">—</span>
                                        @else
                                            <x-truncate :text="(string) $value" />
                                        @endif
                                    </td>
                                @endforeach
                                <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                    <x-admin-row-actions :actions="[
                                        ['wireClick' => 'edit(' . $record->id . ')', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => count($selectedIds) > 0],
                                        ['wireClick' => 'confirmDelete(' . $record->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => count($selectedIds) > 0],
                                    ]" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $listFields->count() + 2 }}" class="px-6 py-16 text-center">
                                    <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.5">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                    </svg>
                                    <p class="text-sm text-zinc-600">{{ $hasFilters ? 'No records match your search or filters.' : 'No records yet.' }}</p>
                                    @unless ($hasFilters)
                                        <flux:button class="mt-3" size="sm" icon="plus" wire:click="create">Add the first {{ strtolower($singular) }}</flux:button>
                                    @endunless
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-3">
            {{ $records->links() }}
        </div>
    </div>

    {{-- Create / edit modal --}}
    <flux:modal name="crud-record-form" class="md:w-[44rem] max-w-full"
        x-on:open-modal.window="if ($event.detail.name === 'crud-record-form') $flux.modal('crud-record-form').show()"
        x-on:close-modal.window="if ($event.detail.name === 'crud-record-form') $flux.modal('crud-record-form').close()">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit '.$singular : 'New '.$singular }}</flux:heading>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($crud->fields as $f)
                    @php $wide = in_array($f['type'], ['textarea', 'image'], true); @endphp
                    <flux:field wire:key="f-{{ $f['name'] }}" class="{{ $wide ? 'md:col-span-2' : '' }}">
                        @if ($f['type'] === 'boolean')
                            <flux:checkbox wire:model="form.{{ $f['name'] }}" label="{{ $f['label'] }}" />
                        @else
                            <flux:label>{{ $f['label'] }}@if ($f['required'] ?? false)<span class="text-red-500 ml-0.5">*</span>@endif</flux:label>
                            @if ($f['type'] === 'textarea')
                                <flux:textarea wire:model="form.{{ $f['name'] }}" rows="4" />
                            @elseif ($f['type'] === 'select')
                                <flux:select wire:model="form.{{ $f['name'] }}" placeholder="Select...">
                                    @foreach (\App\Support\Cruds::options($f) as $option)
                                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @elseif ($f['type'] === 'image')
                                @php $current = $form[$f['name']] ?? ''; $upload = $uploads[$f['name']] ?? null; @endphp
                                <div class="flex items-center gap-3">
                                    @if ($upload)
                                        <img src="{{ $upload->temporaryUrl() }}" alt="" class="size-16 rounded-lg object-cover border border-zinc-200">
                                    @elseif ($current)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($current) }}" alt="" class="size-16 rounded-lg object-cover border border-zinc-200">
                                    @endif
                                    <input type="file" accept="image/*" wire:model="uploads.{{ $f['name'] }}"
                                        class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-600 hover:file:bg-indigo-100">
                                    @if ($upload || $current)
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeImage('{{ $f['name'] }}')">Remove</flux:button>
                                    @endif
                                </div>
                                <div wire:loading wire:target="uploads.{{ $f['name'] }}" class="text-xs text-zinc-500">Uploading…</div>
                                <flux:error name="uploads.{{ $f['name'] }}" />
                            @else
                                <flux:input wire:model="form.{{ $f['name'] }}"
                                    :type="match ($f['type']) { 'number', 'decimal' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', 'email' => 'email', 'url' => 'url', default => 'text' }"
                                    :step="$f['type'] === 'decimal' ? '0.01' : null" />
                            @endif
                        @endif
                        <flux:error name="form.{{ $f['name'] }}" />
                    </flux:field>
                @endforeach
            </div>

            <div class="flex gap-2 pt-1">
                <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal name="crud-record-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'crud-record-delete') $flux.modal('crud-record-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'crud-record-delete') $flux.modal('crud-record-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ strtolower($singular) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">Delete</button>
                <flux:modal.close><flux:button size="sm" variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk Delete Modal --}}
    <flux:modal name="crud-record-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'crud-record-bulk-delete') $flux.modal('crud-record-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'crud-record-bulk-delete') $flux.modal('crud-record-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('record', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="bulkDelete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">Delete</button>
                <flux:modal.close><flux:button size="sm" variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
