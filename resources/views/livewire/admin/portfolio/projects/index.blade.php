{{-- A single root element wraps the whole file (Livewire requires exactly
     one) — see the note in livewire/admin/services/index.blade.php. --}}
<div>

    {{-- Rendered inside the component's own template rather than pushed into
         @stack('page-header-actions'), which is only flushed on the initial
         full-page load and so would freeze on $selectedIds — same reason the
         Services index does it this way. --}}
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.portfolio-projects'])
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if (count($selectedIds) > 0)
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
                </flux:button>
            @endif
            <div class="page-header-actions flex items-center gap-2 shrink-0">
                <flux:button variant="ghost" size="sm" icon="plus" href="{{ route('admin.portfolio-projects.create') }}" wire:navigate>
                    New project
                </flux:button>
            </div>
        </div>
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
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
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search projects…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table — rows are draggable to set the order the theme prints them in --}}
    <div class="overflow-x-auto"
        x-data="{
            init() {
                if (typeof Sortable === 'undefined') return;
                new Sortable(this.$refs.sortableRows, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: () => {
                        const rows = [...this.$refs.sortableRows.querySelectorAll('[data-project-id]')];
                        $wire.reorder(rows.map(r => parseInt(r.dataset.projectId)));
                    }
                });
            }
        }">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:5%">
                    <col style="width:7%">
                    <col style="width:7%">
                    <col style="width:28%">
                    <col style="width:13%">
                    <col style="width:13%">
                    <col style="width:12%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Icon</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Badge</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Created by</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableRows" class="divide-y divide-gray-200">
                    @forelse ($projects as $project)
                        <tr wire:key="project-{{ $project->id }}" data-project-id="{{ $project->id }}"
                            class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($project->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $project->id }}) }">

                            {{-- Bulk-select checkbox — its own dedicated column so it never
                                 crowds into the ID column; stays hidden until a selection is
                                 already in progress. --}}
                            <td class="px-2 py-2 text-center" @click.stop>
                                @if (count($selectedIds) > 0)
                                    <input type="checkbox" wire:click="toggleSelect({{ $project->id }})"
                                        @checked(in_array($project->id, $selectedIds, true))
                                        class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                @endif
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
                            <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                <x-copy-text :text="$project->id" class="text-xs text-zinc-500">{{ $project->id }}</x-copy-text>
                            </td>

                            {{-- Icon (emoji) --}}
                            <td class="px-4 py-2 text-base">{{ $project->icon ?: '—' }}</td>

                            {{-- Title (+ tech chips) --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug"
                                    @if ($project->getTranslation('title', 'bn', false))
                                        title="{{ $project->getTranslation('title', 'en', false) }} — {{ $project->getTranslation('title', 'bn', false) }}"
                                    @endif>
                                    <x-truncate :text="$project->getTranslation('title', 'en', false)" />
                                </div>
                                @if (filled($project->tech))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach (array_slice($project->tech, 0, 3) as $tech)
                                            <span class="px-1.5 py-0.5 rounded bg-zinc-100 text-[10.5px] text-zinc-600">{{ $tech }}</span>
                                        @endforeach
                                        @if (count($project->tech) > 3)
                                            <span class="px-1.5 py-0.5 rounded bg-zinc-100 text-[10.5px] text-zinc-500">+{{ count($project->tech) - 3 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Badge --}}
                            <td class="px-4 py-2 text-sm text-zinc-700">
                                <x-truncate :text="$project->stats ?: '—'" />
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                @if ($project->status === 'active')
                                    <button type="button" wire:click="toggleStatus({{ $project->id }})"
                                        aria-label="Deactivate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $project->id }})"
                                        aria-label="Activate"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </button>
                                @endif
                            </td>

                            {{-- Created by --}}
                            <td class="px-4 py-2 text-sm text-zinc-500">
                                {{ $project->creator?->name ?? '—' }}
                            </td>

                            {{-- Actions — disabled while a bulk selection is active, so the
                                 per-row actions can't conflict with the bulk toolbar above. --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                @php $bulkActive = count($selectedIds) > 0; @endphp
                                <x-admin-row-actions :actions="[
                                    ['href' => route('admin.portfolio-projects.edit', $project->id), 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => $bulkActive],
                                    ['wireClick' => 'confirmDelete(' . $project->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive],
                                ]" />
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <flux:icon.folder-open class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">No projects found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $projects->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="project-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'project-delete') $flux.modal('project-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'project-delete') $flux.modal('project-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete project?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The project will be soft-deleted.
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
    <flux:modal name="project-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'project-bulk-delete') $flux.modal('project-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'project-bulk-delete') $flux.modal('project-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('project', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The selected projects will be soft-deleted.
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
