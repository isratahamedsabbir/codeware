<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.cruds') }}" wire:navigate>Back</flux:button>
    @endpush

    <div class="grid gap-3 lg:grid-cols-3 items-start">

        {{-- Details --}}
        <x-admin-section-card variant="postbox" title="CRUD Details" :collapsible="false" class="w-full lg:col-span-1" body-class="p-3">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Name<span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model.live.debounce.300ms="name" placeholder="e.g. Testimonials" :disabled="(bool) $crudId" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Sidebar icon</flux:label>
                    <div class="flex items-center gap-2">
                        <flux:input wire:model.live.debounce.300ms="icon" placeholder="home" class="font-mono" />
                        <button type="button" title="Choose an icon" x-on:click="$flux.modal('crud-icon-picker').show()"
                            class="w-9 h-9 shrink-0 rounded-lg border border-zinc-200 flex items-center justify-center text-zinc-500 hover:bg-zinc-50 hover:border-indigo-300 cursor-pointer">
                            @if (\App\Models\MenuItem::iconExists($icon))
                                <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4.5" />
                            @else
                                <flux:icon.question-mark-circle class="size-4.5 text-zinc-300" />
                            @endif
                        </button>
                    </div>
                    <flux:description>Type an icon name or click the box to choose one.</flux:description>
                    <flux:error name="icon" />

                    <flux:modal name="crud-icon-picker" class="md:w-md max-w-full">
                        <div class="space-y-4">
                            <flux:heading size="lg">Choose an icon</flux:heading>
                            <div class="grid grid-cols-6 gap-2">
                                @foreach ($iconChoices as $choice)
                                    <button type="button" wire:click="pickIcon('{{ $choice }}')" title="{{ $choice }}"
                                        class="size-12 inline-flex items-center justify-center rounded-lg border transition-colors cursor-pointer
                                            {{ $icon === $choice ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-zinc-200 text-zinc-500 hover:bg-zinc-50' }}">
                                        <x-dynamic-component :component="'flux::icon.'.$choice" class="size-6" />
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </flux:modal>
                </flux:field>
            </div>
        </x-admin-section-card>

        {{-- Fields --}}
        <x-admin-section-card variant="postbox" title="Fields" :collapsible="false" class="w-full lg:col-span-2" body-class="p-3">
            <div class="space-y-2">
                @foreach ($fields as $i => $field)
                    @php $isExisting = in_array($field['name'] ?? null, $existingNames, true); @endphp
                    <div wire:key="field-{{ $i }}-{{ $field['name'] ?? 'new' }}" class="rounded-lg border border-zinc-200 bg-white">
                        <div class="flex items-center gap-2 border-b border-zinc-100 bg-zinc-50 px-3 py-1.5 rounded-t-lg">
                            <span class="inline-flex size-5 items-center justify-center rounded-full bg-indigo-100 text-[11px] font-semibold text-indigo-600">{{ $i + 1 }}</span>
                            <span class="text-xs text-zinc-500 truncate">
                                @if ($isExisting)
                                    column <code class="text-zinc-700">{{ $field['name'] }}</code> · {{ $types[$field['type']] ?? $field['type'] }}
                                @else
                                    <span class="text-emerald-600 font-medium">New field</span>
                                @endif
                            </span>
                            <div class="ml-auto flex items-center gap-0.5">
                                <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="moveField({{ $i }}, -1)" :disabled="$i === 0" inset />
                                <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="moveField({{ $i }}, 1)" :disabled="$i === count($fields) - 1" inset />
                                <flux:button size="xs" variant="ghost" icon="trash" class="text-red-600" inset
                                    wire:click="removeField({{ $i }})"
                                    @if ($isExisting) wire:confirm="Remove this field? Its column and all its data are deleted when you click Update." @endif />
                            </div>
                        </div>
                        <div class="grid gap-3 p-3 md:grid-cols-12 items-start">
                            <div class="md:col-span-5">
                                <flux:input wire:model="fields.{{ $i }}.label" placeholder="Label (e.g. Customer name)" />
                                <flux:error name="fields.{{ $i }}.label" />
                            </div>
                            <div class="md:col-span-3">
                                <flux:select wire:model.live="fields.{{ $i }}.type" :disabled="$isExisting">
                                    @foreach ($types as $key => $label)
                                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="md:col-span-4 flex items-center gap-4 pt-1.5">
                                <flux:checkbox wire:model="fields.{{ $i }}.required" label="Required" />
                                <flux:checkbox wire:model="fields.{{ $i }}.in_list" label="Show in list" />
                            </div>
                            @if (($field['type'] ?? '') === 'select')
                                <div class="md:col-span-12">
                                    <flux:input wire:model="fields.{{ $i }}.options" placeholder="Options, comma separated — e.g. Draft, Published, Archived" />
                                    <flux:error name="fields.{{ $i }}.options" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <flux:error name="fields" />

            <flux:button class="mt-3" size="sm" icon="plus" wire:click="addField">Add field</flux:button>

            @if ($crudId)
                <p class="mt-3 text-xs text-zinc-500">Existing fields keep their type (they are real database columns). You can rename them, reorder them, add new ones, or remove one — which deletes its column.</p>
            @endif

            <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
                <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    <span wire:loading.remove wire:target="save">{{ $crudId ? 'Update CRUD' : 'Create table & menu' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </x-admin-section-card>
    </div>
</div>
