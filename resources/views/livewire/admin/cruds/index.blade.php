<div>
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials._admin-breadcrumbs', ['routeName' => 'admin.cruds', 'title' => 'CRUD Builder'])
        </div>
        <div class="page-header-actions flex items-center gap-2 shrink-0">
            <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.cruds.create') }}" wire:navigate>
                New CRUD
            </flux:button>
        </div>
    </div>

    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                    <colgroup>
                        <col style="width:7%">
                        <col style="width:30%">
                        <col style="width:30%">
                        <col style="width:13%">
                        <col style="width:20%">
                    </colgroup>
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Table</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Fields</th>
                            <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($cruds as $crud)
                            <tr wire:key="crud-{{ $crud->id }}" class="group/row hover:bg-indigo-50/30 transition-colors cursor-default">
                                <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                    <x-copy-text :text="$crud->id" class="text-xs text-zinc-500">{{ $crud->id }}</x-copy-text>
                                </td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('admin.cruds.records', $crud->slug) }}" wire:navigate
                                        class="inline-flex items-center gap-2 font-medium text-zinc-900 text-sm leading-snug hover:text-indigo-600">
                                        <x-dynamic-component :component="'flux::icon.'.(\App\Models\MenuItem::iconExists($crud->icon) ? $crud->icon : 'rectangle-stack')" class="size-4.5 text-zinc-400" />
                                        <x-truncate :text="$crud->name" />
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-zinc-600">
                                    <x-truncate :text="$crud->table_name" />
                                </td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 border border-indigo-200">
                                        {{ count($crud->fields) }} {{ \Illuminate\Support\Str::plural('field', count($crud->fields)) }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200">
                                        {{ $counts[$crud->id] ?? 0 }} {{ \Illuminate\Support\Str::plural('record', $counts[$crud->id] ?? 0) }}
                                    </span>
                                </td>
                                <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                    <x-admin-row-actions :actions="[
                                        ['href' => route('admin.cruds.records', $crud->slug), 'icon' => 'document', 'label' => 'Records', 'color' => 'emerald-500'],
                                        ['href' => route('admin.cruds.edit', $crud->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                        ['wireClick' => 'confirmDelete(' . $crud->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                    ]" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.5">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                    </svg>
                                    <p class="text-sm text-zinc-600">No CRUDs yet — create your first one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="h-3"></div>

        {{-- Delete Modal --}}
        <flux:modal name="crud-delete" class="md:w-96"
            x-on:open-modal.window="if ($event.detail.name === 'crud-delete') $flux.modal('crud-delete').show()"
            x-on:close-modal.window="if ($event.detail.name === 'crud-delete') $flux.modal('crud-delete').close()">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        </svg>
                    </div>
                    <flux:heading>Delete CRUD?</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-500">This drops the database table together with all its records. This action cannot be undone.</flux:text>
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
</div>
