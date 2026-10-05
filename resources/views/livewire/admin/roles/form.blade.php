<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.roles') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="space-y-5">

        {{-- ── ROLE NAME ── --}}
        <x-admin-section-card variant="postbox" persist-key="role-details" title="Role Details" :collapsed="false" body-class="p-3">
            <div class="space-y-5">
                <flux:field>
                    <flux:label>Role name <span class="text-red-500 ml-0.5">*</span><x-field-hint text='Saved in lowercase, e.g. <span class="font-mono">Content Manager</span> becomes <span class="font-mono">content-manager</span>' /></flux:label>
                    <flux:input wire:model="name" placeholder="e.g. manager, editor, author" />
                    <flux:error name="name" />
                </flux:field>

                @if ($roleId && $role->name === 'admin')
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        The <strong>admin</strong> role always has every permission and cannot be renamed.
                    </div>
                @endif
            </div>
        </x-admin-section-card>

        {{-- ── PERMISSIONS ── --}}
        <x-admin-section-card variant="postbox" persist-key="role-permissions" title="Permissions" :collapsed="false"
            body-class="p-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <x-slot:actions>
                <span class="text-xs text-zinc-500">{{ count($selectedPermissions) }} selected</span>
            </x-slot:actions>

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
        </x-admin-section-card>

        {{-- ── LOGIN SECURITY ── --}}
        <x-admin-section-card variant="postbox" persist-key="role-login-security" title="Login Security" :collapsed="false"
            body-class="p-3">
            <div class="space-y-4">
                <p class="text-xs text-zinc-500">
                    What someone with this role has to get past on the way in. Both are off by default and
                    both can also be flipped from the roles table.
                </p>

                <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Two-factor authentication (2FA)</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Require a second factor — an
                            authenticator app or a passkey — before this role can sign in. Anyone in the role with
                            no factor yet is held on an enrolment screen; they cannot get in until they set one up.</p>
                    </div>
                    <flux:switch wire:model="mfaEnabled" aria-label="Require 2FA" title="Require a second factor for this role" class="shrink-0" />
                </div>

                <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">reCAPTCHA</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Show a reCAPTCHA on this role's login form.
                            Needs both keys set under <a href="{{ route('admin.env') }}" wire:navigate
                                class="text-primary hover:underline">Settings → Env</a> — without them the widget
                                cannot be rendered and the requirement is skipped.</p>
                    </div>
                    <flux:switch wire:model="recaptchaEnabled" aria-label="Require reCAPTCHA" title="Ask for a reCAPTCHA on this role's login" class="shrink-0" />
                </div>

                <flux:error name="mfaEnabled" />
                <flux:error name="recaptchaEnabled" />
            </div>
        </x-admin-section-card>

        {{-- ── FOOTER ── --}}
        <div class="flex items-center gap-3 flex-wrap">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
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
