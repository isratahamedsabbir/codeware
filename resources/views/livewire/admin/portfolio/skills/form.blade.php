<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.portfolio-skills') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-3">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::translatable() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label>
                            Name
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model="name.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'e.g. Laravel' : 'Name ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description.{{ $language->code }}" rows="3"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'A one-line note shown under the name' : 'Description ('.($language->native_name ?: $language->name).')' }}" />
                        <flux:error name="description.{{ $language->code }}" />
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach

            <flux:field>
                <flux:label>Group <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input wire:model="group" list="portfolio-skill-groups" placeholder="Backend" />
                {{-- Existing groups offered as suggestions. The theme builds one column
                     per distinct group, so an existing heading is usually what's wanted —
                     but the field stays free text, so a new one needs no migration. --}}
                <datalist id="portfolio-skill-groups">
                    @foreach (\App\Models\PortfolioSkill::query()->distinct()->orderBy('group')->pluck('group') as $existingGroup)
                        <option value="{{ $existingGroup }}"></option>
                    @endforeach
                </datalist>
                <flux:error name="group" />
            </flux:field>
        </x-admin-locale-tabs>

        <flux:field>
            <flux:label>Icon <x-field-hint text="An emoji, shown next to the name" /></flux:label>
            <flux:input wire:model="icon" placeholder="⚙️" maxlength="20" />
            <flux:error name="icon" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$skillId ? 'Update Skill' : 'Create Skill'" />
        </div>

    </div>
</div>
