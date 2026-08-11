<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Settings</flux:heading>
            <flux:subheading>Configure your site settings and layout.</flux:subheading>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm dark:bg-green-950 dark:border-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Alpine tab switcher: General | Layout | Media | Security --}}
    <div x-data="{ tab: 'general' }">

        {{-- Tab nav --}}
        <div class="flex gap-0 mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <button type="button" @click="tab = 'general'"
                :class="tab==='general'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">General</button>
            <button type="button" @click="tab = 'layout'"
                :class="tab==='layout'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Layout</button>
            <button type="button" @click="tab = 'media'"
                :class="tab==='media'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Media Library</button>
            <button type="button" @click="tab = 'security'"
                :class="tab==='security'?'border-b-2 border-blue-600 text-blue-600 font-medium':'text-zinc-500 hover:text-zinc-700'"
                class="px-5 py-3 text-sm -mb-px">Security</button>
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-2xl space-y-6">
                @foreach ($groupedSettings as $group => $items)
                    <div>
                        <flux:heading size="sm" class="mb-3 capitalize">{{ $group ?? 'General' }}</flux:heading>
                        <div class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                            @foreach ($items as $setting)
                                <flux:field>
                                    <flux:label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</flux:label>
                                    @if ($setting->type === 'boolean')
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox"
                                                wire:model="settings.{{ $setting->key }}"
                                                class="rounded border-zinc-300 text-blue-600" />
                                            <span class="text-sm text-zinc-600">Enable</span>
                                        </div>
                                    @elseif ($setting->type === 'color')
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg border border-zinc-300 shrink-0"
                                                 style="background-color: {{ $settings[$setting->key] ?? '#ffffff' }}"
                                                 x-data
                                                 :style="'background-color: ' + ($wire.settings['{{ $setting->key }}'] || '#ffffff')"></div>
                                            <flux:input wire:model="settings.{{ $setting->key }}" placeholder="#000000" class="flex-1 font-mono" />
                                        </div>
                                    @else
                                        <flux:input wire:model="settings.{{ $setting->key }}" />
                                    @endif
                                </flux:field>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                    Save Settings
                </flux:button>
            </div>
        </div>

        {{-- Layout tab --}}
        <div x-show="tab === 'layout'">
            <div class="max-w-2xl space-y-4">
                <flux:text class="text-zinc-500">
                    Edit your site header and footer using the Puck visual editor. Changes open in a new tab.
                </flux:text>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                        <flux:heading size="sm" class="mb-2">Header</flux:heading>
                        <flux:text class="text-sm text-zinc-500 mb-4">Edit the site-wide header layout and navigation.</flux:text>
                        <a href="{{ $this->getEditorUrl('header') }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Header
                        </a>
                    </div>

                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                        <flux:heading size="sm" class="mb-2">Footer</flux:heading>
                        <flux:text class="text-sm text-zinc-500 mb-4">Edit the site-wide footer layout and links.</flux:text>
                        <a href="{{ $this->getEditorUrl('footer') }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Footer
                        </a>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-zinc-50 dark:bg-zinc-800/50">
                    <h5 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-2">How it works</h5>
                    <ul class="text-xs text-zinc-500 space-y-1">
                        <li>1. Click "Edit Header" or "Edit Footer" to open the Puck visual editor</li>
                        <li>2. Make your changes using the drag-and-drop interface</li>
                        <li>3. Save your changes in Puck - they will be stored automatically</li>
                        <li>4. The changes will appear on your live site immediately</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Media tab --}}
        <div x-show="tab === 'media'">
            <livewire:admin.media-library.index />
        </div>

        {{-- Security tab --}}
        <div x-show="tab === 'security'">
            <div class="max-w-2xl space-y-6">
                <flux:heading size="sm">Update Password</flux:heading>
                <flux:subheading>Ensure your account is using a strong password.</flux:subheading>

                <form method="POST" wire:submit="updatePassword" class="space-y-4">
                    <flux:input
                        wire:model="current_password"
                        label="Current password"
                        type="password"
                        required
                        autocomplete="current-password"
                        viewable
                    />
                    <flux:input
                        wire:model="password"
                        label="New password"
                        type="password"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:input
                        wire:model="password_confirmation"
                        label="Confirm password"
                        type="password"
                        required
                        autocomplete="new-password"
                        viewable
                    />

                    <div class="flex items-center gap-4">
                        <flux:button variant="primary" type="submit">
                            Save
                        </flux:button>

                        <x-action-message class="me-3" on="password-updated">
                            Saved.
                        </x-action-message>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
