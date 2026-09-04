@push('page-header-actions')
    <flux:modal.trigger name="mail-settings">
        <flux:button variant="outline" size="sm" icon="envelope">
            Mail Settings
        </flux:button>
    </flux:modal.trigger>
@endpush

<div class="space-y-4">

    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">

        {{-- ── Sidebar: Template Inventory ──────────────────────────────────── --}}
        <aside class="space-y-3 lg:sticky lg:top-6 self-start">
            <div class="flex items-center justify-between px-1">
                <span class="text-[10px] font-medium uppercase tracking-widest text-slate-500">Template Inventory</span>
                <span
                    class="text-[10px] font-medium text-slate-500 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded-md">
                    {{ count($templates) }}
                </span>
            </div>
            <div class="space-y-1.5 max-h-[700px] overflow-y-auto pr-1 custom-scrollbar">
                @foreach ($templates as $template)
                    <button type="button" wire:key="email-template-list-{{ $template->id }}"
                        wire:click="selectTemplate({{ $template->id }})"
                        class="w-full text-left transition-all duration-150">
                        <div
                            class="relative rounded-lg border p-3.5 transition-all duration-150
                            {{ $selectedTemplateId === $template->id
                                ? 'bg-white border-secondary ring-1 ring-secondary/30'
                                : 'bg-white border-slate-200 hover:bg-white hover:border-slate-300' }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <span
                                    class="text-[9px] font-medium uppercase tracking-wide
                                    {{ $template->active ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $template->active ? 'Live' : 'Draft' }}
                                </span>
                                <span class="font-mono text-[9px] text-slate-400">#{{ $template->id }}</span>
                            </div>
                            <p class="text-sm text-slate-800 truncate">{{ $template->name }}</p>
                            <p class="mt-0.5 text-[10px] text-slate-400 truncate font-mono">{{ $template->key }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </aside>

        {{-- ── Main Workspace ────────────────────────────────────────────────── --}}
        <main>
            @if ($selectedTemplateId === null)
                <div
                    class="bg-white border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center p-20 text-center">
                    <div class="rounded-full bg-slate-50 border border-slate-200 p-4 mb-4">
                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">No email templates found</h3>
                    <p class="mt-1 text-xs text-slate-400">Run the EmailTemplatesSeeder to create the default template set.</p>
                </div>
            @else
                <div class="space-y-5">

                    {{-- Editor Interface --}}
                    <section class="bg-white border border-slate-200 rounded-xl p-6">
                        <div class="grid gap-5">

                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Name</label>
                                <input type="text" wire:model="name"
                                    class="block w-full h-9 rounded-lg border border-slate-300 text-sm text-slate-800 px-3 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none" />
                                @error('name')
                                    <span class="text-[11px] text-rose-600 font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Subject</label>
                                <input type="text" wire:model="subjectTemplate"
                                    class="block w-full h-9 rounded-lg border border-slate-300 text-sm text-slate-800 px-3 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none" />
                                @error('subjectTemplate')
                                    <span class="text-[11px] text-rose-600 font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Email
                                    Body</label>
                                <livewire:jodit-text-editor wire:model="bodyTemplate" :height="360" />
                                <p class="text-[11px] text-slate-400">Use <code
                                        class="font-mono bg-slate-100 px-1 py-0.5 rounded">@{{ variable_name }}</code>
                                    placeholders in the subject and body.</p>
                                @error('bodyTemplate')
                                    <span class="text-[11px] text-rose-600 font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Runtime
                                    Variables (comma separated)</label>
                                <input type="text" wire:model="variablesList"
                                    placeholder="e.g. customer_name, order_id, order_total"
                                    class="block w-full h-9 rounded-lg border border-slate-300 text-sm text-slate-800 px-3 font-mono focus:border-secondary focus:ring-1 focus:ring-secondary outline-none" />
                                @error('variablesList')
                                    <span class="text-[11px] text-rose-600 font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="active"
                                        class="h-4 w-4 rounded border-slate-300 text-secondary focus:ring-secondary">
                                    <span class="text-sm text-slate-700">Active</span>
                                </label>
                            </div>

                        </div>

                        <div class="flex items-center mt-6 pt-5 border-t border-slate-100">
                            <button type="button" wire:click="save"
                                class="inline-flex items-center h-9 px-5 rounded-lg bg-secondary hover:bg-secondary-hover text-white text-[11px] font-medium uppercase tracking-wide transition-colors">
                                Update Template
                            </button>
                        </div>
                    </section>

                    {{-- Preview Environment --}}
                    <section class="bg-white border border-slate-200 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">Simulation environment</h3>
                                <p class="mt-0.5 text-[10px] font-medium text-slate-400 font-mono">{{ $templateKey }}
                                </p>
                            </div>
                            <button type="button" wire:click="generatePreview"
                                class="inline-flex items-center gap-1.5 h-9 px-4 rounded-lg border border-slate-300 hover:bg-white text-slate-700 text-[11px] font-medium uppercase tracking-wide transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Sync Preview
                            </button>
                        </div>

                        <div class="grid gap-5 lg:grid-cols-2">

                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-medium uppercase tracking-widest text-slate-500 mb-1.5">Simulation
                                    Data (JSON Payload)</label>
                                <textarea wire:model="previewVariablesJson" rows="8"
                                    class="block w-full rounded-lg border border-slate-300 font-mono text-[11px] text-slate-700 p-3 bg-white focus:border-secondary focus:ring-1 focus:ring-secondary outline-none resize-none"></textarea>
                            </div>

                            <div class="space-y-4">
                                @if ($previewSubject !== '')
                                    <div class="p-4 rounded-lg bg-white border border-slate-200">
                                        <p
                                            class="text-[10px] font-medium uppercase tracking-widest text-slate-400 mb-1.5">
                                            Simulated Subject Line</p>
                                        <p class="text-sm font-semibold text-slate-900">{{ $previewSubject }}</p>
                                    </div>
                                @endif

                                @if ($previewBody !== '')
                                    <div class="bg-white border border-slate-200 rounded-lg max-h-[320px] overflow-y-auto">
                                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                                            <p class="text-[10px] font-medium uppercase tracking-widest text-slate-400">
                                                Rendered Body</p>
                                        </div>
                                        <div class="p-5 text-sm text-slate-800 leading-relaxed preview-canvas">
                                            {!! $previewBody !!}
                                        </div>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </section>

                </div>
            @endif
        </main>
    </div>

    {{-- Mail settings --}}
    <flux:modal name="mail-settings" class="md:w-[560px]">
        <div class="space-y-5">
            <flux:heading>{{ __('Mail Settings') }}</flux:heading>

            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                <strong>{{ __('Careful') }}:</strong>
                {{ __('This edits the live .env file this server runs on. A backup of the current file is saved automatically before every change.') }}
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($this->mailFields() as $key => $meta)
                    <flux:field class="{{ in_array($key, ['MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'], true) ? 'sm:col-span-2' : '' }}">
                        <flux:label>{{ __($meta['label']) }}</flux:label>
                        @if ($meta['type'] === 'select')
                            <select wire:model="mailSettings.{{ $key }}"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                                @foreach ($meta['options'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif ($meta['type'] === 'password')
                            <flux:input type="password" wire:model="mailSettings.{{ $key }}" />
                        @else
                            <flux:input wire:model="mailSettings.{{ $key }}" />
                        @endif
                        <flux:error name="mailSettings.{{ $key }}" />
                    </flux:field>
                @endforeach
            </div>

            <div class="flex gap-2 pt-1">
                <flux:button variant="primary" wire:click="confirmSaveMailSettings" wire:loading.attr="disabled">
                    {{ __('Save Mail Settings') }}
                </flux:button>
            </div>

            <div class="border-t border-zinc-100 pt-5 space-y-3">
                <div>
                    <flux:heading size="sm">{{ __('Send Test Email') }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 -mt-1">
                        {{ __('Sends the "Test Email (Mail Settings)" template using whatever mail settings are currently saved.') }}
                    </flux:text>
                </div>
                <div class="flex items-start gap-2">
                    <div class="flex-1">
                        <flux:input type="email" wire:model="testEmailAddress" placeholder="you@example.com" />
                        <flux:error name="testEmailAddress" />
                    </div>
                    <flux:button variant="outline" wire:click="sendTestEmail" wire:loading.attr="disabled" wire:target="sendTestEmail">
                        {{ __('Send Test Email') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    {{-- Mail settings save confirmation --}}
    <flux:modal name="mail-settings-save-confirm" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'mail-settings-save-confirm') $flux.modal('mail-settings-save-confirm').show()"
        x-on:close-modal.window="if ($event.detail.name === 'mail-settings-save-confirm') $flux.modal('mail-settings-save-confirm').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500" />
                </div>
                <flux:heading>{{ __('Save mail settings?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This overwrites the live .env file and clears the configuration cache. Use "Send Test Email" afterwards to confirm the new settings actually work.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="saveMailSettings" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Save anyway') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
