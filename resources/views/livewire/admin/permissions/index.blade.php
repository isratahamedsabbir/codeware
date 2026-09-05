@push('page-header-actions')
    <flux:button variant="ghost" size="sm" icon="plus" wire:click="openCreateModal">
        New permission
    </flux:button>
@endpush

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <div class="relative max-w-xs w-full ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search permissions…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:55%">
                    <col style="width:20%">
                    <col style="width:20%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Permission</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Guard</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Used by roles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($permissions as $permission)
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-2 py-2.5 text-center text-xs text-zinc-500">
                                {{ $permission->id }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
                                    </div>
                                    <span class="font-mono text-xs text-zinc-700"><x-truncate :text="$permission->name" /></span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="text-xs text-zinc-600">{{ $permission->guard_name }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="text-xs text-zinc-600">{{ $permission->roles_count }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <p class="text-sm text-zinc-600">No permissions found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $permissions->links() }}
    </div>

    {{-- Create Modal --}}
    <flux:modal name="permission-create" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'permission-create') $flux.modal('permission-create').show()"
        x-on:close-modal.window="if ($event.detail.name === 'permission-create') $flux.modal('permission-create').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <flux:heading>New permission</flux:heading>
            </div>
            <flux:field>
                <flux:label>Permission name <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input wire:model="newName" placeholder="e.g. export reports" />
                <p class="text-xs text-zinc-400 mt-1">Saved in lowercase, e.g. <span class="font-mono">Export Reports</span>
                    becomes <span class="font-mono">export-reports</span></p>
                <flux:error name="newName" />
            </flux:field>
            <div class="flex gap-2 pt-1">
                <button wire:click="create"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white transition-colors border-none cursor-pointer">
                    Create
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
