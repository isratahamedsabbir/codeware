<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.portfolio-projects') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="w-full max-w-2xl bg-white rounded-[5px] shadow-sm p-6 space-y-3">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::translatable() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label>
                            Title
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model="title.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'Project name' : 'Project name ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="title.{{ $language->code }}" />@endif
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description.{{ $language->code }}" rows="4"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'What the project does' : 'Description ('.($language->native_name ?: $language->name).')' }}" />
                        <flux:error name="description.{{ $language->code }}" />
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach
        </x-admin-locale-tabs>

        <flux:field>
            <flux:label>Icon <x-field-hint text="An emoji, shown in the card's corner" /></flux:label>
            <flux:input wire:model="icon" placeholder="🚀" maxlength="20" />
            <flux:error name="icon" />
        </flux:field>

        <flux:field>
            <flux:label>Badge <x-field-hint text="Short label in the card's top-right, e.g. 'Open Source'" /></flux:label>
            <flux:input wire:model="stats" placeholder="Open Source" />
            <flux:error name="stats" />
        </flux:field>

        <flux:field>
            <flux:label>Link <x-field-hint text="Where the card's 'View Project' link points" /></flux:label>
            <flux:input wire:model="link" placeholder="https://example.com/project" />
            <flux:error name="link" />
        </flux:field>

        {{-- Technology list — repeated inputs rather than a comma-separated box, so an
             entry containing a comma (or one pasted in with trailing spaces) still lands
             as a single item. Blanks are dropped on save. --}}
        <div>
            <flux:label class="mb-2">Technology</flux:label>
            <div class="space-y-2">
                @foreach ($tech as $index => $item)
                    <div class="flex items-center gap-2">
                        <flux:input wire:model="tech.{{ $index }}" placeholder="Laravel" class="flex-1" />
                        <flux:button size="sm" variant="subtle" square icon="trash"
                            wire:click="removeTech({{ $index }})" aria-label="Remove technology" />
                    </div>
                @endforeach
            </div>
            <flux:error name="tech.*" />
            <flux:button size="sm" variant="ghost" icon="plus" wire:click="addTech" class="mt-2">
                Add technology
            </flux:button>
        </div>

        {{-- Footer --}}
        <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$projectId ? 'Update Project' : 'Create Project'" />
        </div>

    </div>
</div>
