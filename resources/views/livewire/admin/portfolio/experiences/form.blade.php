<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.portfolio-experiences') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-3">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::active() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label>
                            Role
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model="role.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'Job title' : 'Job title ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="role.{{ $language->code }}" />@endif
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>Company</flux:label>
                        <flux:input wire:model="company.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'Company or client name' : 'Company ('.($language->native_name ?: $language->name).')' }}" />
                        <flux:error name="company.{{ $language->code }}" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description.{{ $language->code }}" rows="4"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'What you did there' : 'Description ('.($language->native_name ?: $language->name).')' }}" />
                        <flux:error name="description.{{ $language->code }}" />
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach
        </x-admin-locale-tabs>

        <flux:field>
            <flux:label>Period <x-field-hint text="Printed verbatim, e.g. '2024 - Present'" /></flux:label>
            <flux:input wire:model="period" placeholder="2024 - Present" />
            <flux:error name="period" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$experienceId ? 'Update Experience' : 'Create Experience'" />
        </div>

    </div>
</div>
