<div class="max-w-[1600px] w-full mx-auto flex-1">

    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.roles') }}" wire:navigate
        class="mb-4 border-2">
        Back
    </flux:button>

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg border border-zinc-100 shadow-sm p-6">

            <h1 class="text-lg font-semibold text-zinc-900 mb-5 pb-4 border-b border-zinc-100">
                {{ $roleId ? 'Edit Role' : 'New Role' }}
            </h1>

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

            {{-- Permissions --}}
            <div class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-zinc-900">Permissions</h2>
                    <span class="text-xs text-zinc-500">
                        {{ count($selectedPermissions) }} selected
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($permissionGroups as $group => $data)
                        <div class="border border-zinc-200 rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-50 border-b border-zinc-200">
                                <span class="text-xs font-semibold text-zinc-700 uppercase tracking-wider">
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

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Footer --}}
            <div class="flex justify-end items-center gap-3 border-t border-zinc-100 flex-wrap">
                <a href="{{ route('admin.roles') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg border text-red-600 border-red-200 bg-white hover:bg-red-50 hover:border-red-400 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Cancel
                </a>
                <button wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
                    style="background:#28A745" onmouseover="this.style.background='#218838'"
                    onmouseout="this.style.background='#28A745'">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    {{ $roleId ? 'Update Role' : 'Create Role' }}
                </button>
            </div>

        </div>

    </div>
</div>
