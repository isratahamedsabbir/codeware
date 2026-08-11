<div class="max-w-[1600px] w-full mx-auto flex-1">

    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.users') }}" wire:navigate
        class="mb-4 border-2">
        Back
    </flux:button>

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg border border-zinc-100 shadow-sm p-6">

            <h1 class="text-lg font-semibold text-zinc-900 mb-5 pb-4 border-b border-zinc-100">
                {{ $userId ? 'Edit User' : 'New User' }}
            </h1>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Full name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email <span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model="email" type="email" placeholder="user@example.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Password {{ $userId ? '(leave blank to keep current)' : '' }} @if (!$userId) <span class="text-red-500 ml-0.5">*</span>@endif</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="Min 8 characters" />
                    <flux:error name="password" />
                </flux:field>
            </div>

            {{-- Roles --}}
            <div class="mt-6">
                <h2 class="text-sm font-semibold text-zinc-900 mb-3">Roles</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @forelse ($roles as $role)
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group border border-zinc-200 rounded-lg px-4 py-3 hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors">
                            <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                class="mt-0.5 size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                            <div class="min-w-0">
                                <div class="text-sm text-zinc-800 group-hover:text-zinc-900 font-medium leading-snug">
                                    {{ $role->name }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $role->permissions_count }} permissions
                                </div>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500">No roles available yet. Create one first.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Settings
                </div>
                <div class="px-4 py-3">
                    <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                        <div>
                            <div class="text-sm font-medium text-zinc-800">Full admin access</div>
                            <div class="text-xs text-zinc-500 mt-0.5">Sets <span class="font-mono">is_admin</span> — grants
                                access to the admin panel regardless of roles.</div>
                        </div>
                        <input type="checkbox" wire:model="isAdmin"
                            class="size-4.5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                    </label>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end items-center gap-3 border-t border-zinc-100 flex-wrap">
                <a href="{{ route('admin.users') }}" wire:navigate
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
                    {{ $userId ? 'Update User' : 'Create User' }}
                </button>
            </div>

        </div>

    </div>
</div>
