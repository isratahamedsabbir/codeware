<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.roles') }}" wire:navigate
            class="border border-zinc-800 rounded!">
            Back
        </flux:button>
    @endpush

    <div class="space-y-4">

        {{-- ── ROLE NAME ── --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="space-y-5">
                <flux:field>
                    <flux:label>Role name <span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="e.g. manager, editor, author" />
                    <p class="text-xs text-zinc-400 mt-1">Saved in lowercase, e.g. <span
                            class="font-mono">Content Manager</span> becomes <span class="font-mono">content-manager</span></p>
                    <flux:error name="name" />
                </flux:field>

                @if ($roleId && $role->name === 'admin')
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        The <strong>admin</strong> role always has every permission and cannot be renamed.
                    </div>
                @endif
            </div>
        </div>

        {{-- ── PERMISSIONS ── --}}
        <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-zinc-100">
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Permissions</span>
                <span class="text-xs text-zinc-500">
                    {{ count($selectedPermissions) }} selected
                </span>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($permissionGroups as $group => $data)
                    <div class="border border-zinc-200 rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-50 border-b border-zinc-200">
                            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                                {{ $data['label'] }}
                            </span>
                            <button type="button" wire:click="toggleGroup('{{ $group }}')"
                                class="text-[11px] font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                Toggle all
                            </button>
                        </div>
                        <div class="px-4 py-3 space-y-2">
                            @foreach ($data['permissions'] as $permission)
                                <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}"
                                        class="mt-0.5 size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                                    <span class="text-sm text-zinc-700 group-hover:text-zinc-900 leading-snug">
                                        {{ $permission->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── FOOTER ── --}}
        <div class="flex justify-end items-center gap-3 border-t border-zinc-100 pt-4 flex-wrap">
            <a href="{{ route('admin.roles') }}" wire:navigate
                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-[5px] border text-red-600 border-red-200 bg-white hover:bg-red-50 hover:border-red-400 transition-colors h-10">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
                Cancel
            </a>
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                </svg>
                <span wire:loading.remove wire:target="save">{{ $roleId ? 'Update Role' : 'Create Role' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </div>
</div>
