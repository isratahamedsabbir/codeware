<div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-zinc-100 flex-wrap">
        <div>
            <h1 class="text-lg font-semibold text-zinc-900">Departments</h1>
            <p class="text-sm text-zinc-600 mt-0.5">Manage career departments</p>
        </div>
        <a href="{{ route('admin.departments.create') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white transition-colors"
            style="background:#22c55e" onmouseover="this.style.background='#16a34a'"
            onmouseout="this.style.background='#22c55e'">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            New department
        </a>
    </div>

    {{-- Search --}}
    <div class="px-6 py-3 border-b border-zinc-100">
        <div class="relative max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search departments…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto px-6 py-4">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full border-collapse" style="table-layout:fixed">
                <colgroup>
                    <col style="width:33%">
                    <col style="width:25%">
                    <col style="width:14%">
                    <col style="width:28%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-100">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Slug</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr class="border-b border-zinc-50 hover:bg-indigo-50/30 transition-colors">

                            {{-- Name --}}
                            <td class="px-4 py-3.5">
                                <div class="font-medium text-zinc-900 text-sm leading-snug">
                                    {{ $department->getTranslation('name', 'en', false) }}
                                </div>
                                @if ($department->getTranslation('name', 'bn', false))
                                    <div class="text-xs text-zinc-600 mt-0.5">
                                        {{ $department->getTranslation('name', 'bn', false) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Slug --}}
                            <td class="px-4 py-3.5">
                                <span class="font-mono text-xs text-zinc-600 truncate block">
                                    {{ $department->slug }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3.5">
                                @if ($department->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">

                                    {{-- Edit --}}
                                    <div class="relative group">
                                        <a href="{{ route('admin.departments.edit', $department->id) }}" wire:navigate
                                            aria-label="Edit department"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white hover:-translate-y-px"
                                            style="box-shadow:none"
                                            onmouseover="this.style.boxShadow='0 3px 8px rgba(99,102,241,.35)'"
                                            onmouseout="this.style.boxShadow='none'">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>
                                        <span
                                            class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-indigo-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            Edit
                                            <span
                                                class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-indigo-500"></span>
                                        </span>
                                    </div>

                                    {{-- Delete --}}
                                    <div class="relative group">
                                        <button wire:click="confirmDelete({{ $department->id }})"
                                            aria-label="Delete department"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border transition-all duration-150 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white hover:-translate-y-px"
                                            style="box-shadow:none"
                                            onmouseover="this.style.boxShadow='0 3px 8px rgba(225,29,72,.35)'"
                                            onmouseout="this.style.boxShadow='none'">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                                <path d="M9 6V4h6v2" />
                                            </svg>
                                        </button>
                                        <span
                                            class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-rose-500 text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            Delete
                                            <span
                                                class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-rose-500"></span>
                                        </span>
                                    </div>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                </svg>
                                <p class="text-sm text-zinc-600">No departments found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3 border-t border-zinc-100">
        {{ $departments->links() }}
    </div>

    {{-- Delete Modal --}}
    <flux:modal name="department-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'department-delete') $flux.modal('department-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'department-delete') $flux.modal('department-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete department?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. The department will be soft-deleted.
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    Delete
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
