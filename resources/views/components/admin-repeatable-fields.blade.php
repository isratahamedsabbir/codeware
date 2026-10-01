@props([
    /**
     * The rows to edit, as ThemeSettings\Index::$repeaters holds them.
     *
     * This has to be passed in by every call site. A Blade component is rendered
     * with only its own props and the parent's *component* scope, so a bare
     * `$repeaters` read here does not see the Livewire property two levels up:
     * the list silently renders empty for every theme, and the owner is shown
     * "nothing here yet" over content they can see on the public site. The
     * default is null rather than [] so that mistake is loud, not silent — a
     * genuinely empty list and a list nobody wired up look identical otherwise.
     */
    'repeaters' => null,
    /**
     * The "theme_{slug}_*" key this list is stored under, in that theme's
     * theme.json.
     *
     * Named settingKey rather than a bare `key` on purpose: this attribute *is*
     * the declaration that turns a theme setting into a list, and it has to be
     * tellable apart from a scalar theme setting at a glance and by
     * App\Livewire\Admin\ThemeSettings\Index::declaredRepeaterKeys(), which
     * greps themes' settings.blade.php for `setting-key="theme_..."`. A plain
     * `key="..."` is indistinguishable from a text field's binding, so the
     * admin would load it as a scalar and clobber the rows.
     */
    'settingKey',
    /**
     * Names the list. Optional: a settings screen whose own panel heading already
     * says what the list is passes nothing here, so the name is not repeated.
     * Omitted, the row index and the wiring error fall back to a name derived
     * from the setting key, which keeps every row still addressable.
     */
    'label' => null,
    'hint' => null,
    'emptyTitle' => 'Nothing here yet',
    'emptyHint' => null,
    /**
     * The fields one row is made of, in order. Each entry is
     * ['name' => 'title', 'label' => 'Title', 'placeholder' => null, 'type' => 'text|textarea|media', 'rows' => 3].
     * The first field is the row's identity: a row left blank there is dropped on
     * save, so it must be the field a person is most likely to fill in.
     *
     * `type => 'media'` renders an upload picker bound to this row's own field,
     * so a list can carry a picture per entry (a project screenshot) rather
     * than only text. It always spans the full row width.
     */
    'fields' => [['name' => 'title', 'label' => 'Title']],
    'addLabel' => 'Add row',
    'max' => 24,
    /**
     * How many row cards sit side by side from md up — 2 suits short rows
     * such as the hero stats' value/label pair. Rows with a textarea or a
     * picture read better at the default full width. In two-column mode a
     * card's own fields stack, so each input gets the card's full width.
     */
    'columns' => 1,
])

<div class="space-y-3">
    @if ($label)
        <div class="min-h-9">
            <label class="block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $label }}</label>
            @if ($hint)
                <span class="block text-[11px] leading-tight font-normal text-zinc-400 dark:text-zinc-500">{{ $hint }}</span>
            @endif
        </div>
    @endif

    @php
        // A null here means the call site never passed :repeaters, which is a
        // wiring bug, not an empty list. Say so instead of drawing "nothing here
        // yet" over a section the owner can plainly see on their own site.
        $rows = $repeaters === null ? null : ($repeaters[$settingKey] ?? []);
        $count = $rows === null ? 0 : count($rows);

        // Without an explicit label, name the list after the last word of its key so the
        // row index still reads as words: theme_portfolio_skills -> Skills.
        $name = $label ?: Str::of($settingKey)->afterLast('_')->headline();
    @endphp

    @if ($rows === null)
        <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
            <p class="font-semibold">{{ $name }} is not connected to its data.</p>
            <p class="mt-1 text-xs">
                This list needs <code class="font-mono">:repeaters=&quot;$repeaters&quot;</code> on its
                <code class="font-mono">&lt;x-admin-repeatable-fields&gt;</code> tag, or it cannot
                read its rows. Nothing has been lost — the saved content is still in
                <code class="font-mono">{{ $settingKey }}</code> in this theme's
                <code class="font-mono">theme.json</code>.
            </p>
        </div>
    @else
    <div class="{{ $columns === 2 && $count > 0 ? 'grid gap-2.5 md:grid-cols-2' : 'space-y-2.5' }}">
        @forelse ($rows as $i => $row)
            <div wire:key="repeater-{{ $settingKey }}-{{ $i }}"
                class="group rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/40">

                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="font-mono text-[10px] font-semibold tracking-wider text-zinc-400 uppercase">
                        {{ $name }} {{ $i + 1 }}
                    </span>

                    {{-- Reorder. The storefront prints rows in stored order, so
                         without this the owner cannot promote their best entry. --}}
                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100">
                        <button type="button" wire:click="moveRepeaterRow('{{ $settingKey }}', {{ $i }}, 'up')"
                            @disabled($i === 0)
                            class="flex size-7 cursor-pointer items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 disabled:pointer-events-none disabled:opacity-30 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                            aria-label="Move up">
                            <flux:icon.arrow-up class="size-3.5" />
                        </button>

                        <button type="button" wire:click="moveRepeaterRow('{{ $settingKey }}', {{ $i }}, 'down')"
                            @disabled($i === $count - 1)
                            class="flex size-7 cursor-pointer items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 disabled:pointer-events-none disabled:opacity-30 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                            aria-label="Move down">
                            <flux:icon.arrow-down class="size-3.5" />
                        </button>

                        <button type="button" wire:click="removeRepeaterRow('{{ $settingKey }}', {{ $i }})"
                            wire:confirm="Remove this row?"
                            class="flex size-7 cursor-pointer items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-500/10"
                            aria-label="Remove row">
                            <flux:icon.trash class="size-3.5" />
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 {{ $columns === 2 ? '' : 'sm:grid-cols-2' }}">
                    @foreach ($fields as $field)
                        @php
                            $name = $field['name'];
                            $type = $field['type'] ?? 'text';
                            $wide = $type === 'textarea' || $type === 'media' || ($field['wide'] ?? false);
                        @endphp

                        <div class="{{ $wide ? 'sm:col-span-2' : '' }}">
                            <flux:label>{{ $field['label'] }}</flux:label>

                            @if ($type === 'textarea')
                                <flux:textarea wire:model="repeaters.{{ $settingKey }}.{{ $i }}.{{ $name }}"
                                    class="h-20 resize-none"
                                    placeholder="{{ $field['placeholder'] ?? '' }}" />
                            @elseif ($type === 'media')
                                {{-- The picker is bound to this row's own field, so a list
                                     can carry a picture per entry. Its default 2MB
                                     ceiling is right for a screenshot; a theme that
                                     wants more passes :maxSizeMb on the field. --}}
                                <x-media-picker
                                    model="repeaters.{{ $settingKey }}.{{ $i }}.{{ $name }}"
                                    label="{{ $field['pickerLabel'] ?? $field['label'] }}"
                                    @if (! empty($field['sizeHint'])) size-hint="{{ $field['sizeHint'] }}" @endif
                                    :only-images="$field['onlyImages'] ?? true"
                                    :maxSizeMb="$field['maxSizeMb'] ?? 2"
                                    compact />
                            @else
                                <flux:input wire:model="repeaters.{{ $settingKey }}.{{ $i }}.{{ $name }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-200 py-8 text-center dark:border-zinc-700">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-300">{{ $emptyTitle }}</p>
                @if ($emptyHint)
                    <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $emptyHint }}</p>
                @endif
            </div>
        @endforelse
    </div>

    {{-- :disabled, not @disabled. A Blade directive written inside a component
         tag's attribute list stops Blade's component compiler from matching the
         tag at all: the opening tag is emitted as raw HTML, the closing tag is
         then left unmatched, and the compiled view dies with a parse error
         ("unexpected token endif"). A bound attribute carries the same value
         without putting a directive in the tag. --}}
    <flux:button type="button" size="sm" variant="outline" icon="plus"
        wire:click="addRepeaterRow('{{ $settingKey }}', {{ json_encode(array_column($fields, 'name')) }})"
        :disabled="$count >= $max"
        class="w-full">
        {{ $addLabel }}
    </flux:button>

    @if ($count >= $max)
        <p class="text-[11px] text-zinc-400">Up to {{ $max }} rows.</p>
    @endif
    @endif
</div>
