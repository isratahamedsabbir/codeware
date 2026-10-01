{{--
    The typeface a storefront theme's public pages render in, with a live
    specimen beside the control. See App\Support\ThemeFont for the options and
    for what each one actually does.

    The specimen is the reason this is a component and not a bare <flux:select>.
    A dropdown of family names tells you nothing you can act on: "Roboto" and
    "Trebuchet MS" are both just words, and the only way to know which one you
    want is to see a sentence set in it. So the face you pick is drawn here, at
    the size the storefront will draw it, before you save anything.

    Three things keep that honest rather than decorative:

      - The @font-face for every webfont on offer is declared inline. The panel's
        own bundle carries Plus Jakarta Sans and nothing else (base.css imports
        resources/css/fonts.css), so a face that only exists on the storefront
        would be named here and quietly fall back — which is the one thing a font
        preview must never do. The files, weights and ranges come from
        ThemeFont rather than being retyped, so they cannot drift from the
        declarations the storefront emits.
      - It reads $wire, not a local copy of the value. wire:model on the select
        is deferred, so the specimen moves the instant the owner picks something
        and puts itself back on the next render — which is what Discard is. A
        copy held in Alpine state would need a reset of its own and would
        eventually disagree with the dropdown it is supposed to be previewing.
      - "Theme default" is previewed as a real face rather than as nothing. It
        means "leave this theme alone", so the face in question is whatever the
        theme already ships, which is what ownFace names.

    ownFace is the ThemeFont option whose stack is this theme's own typeface —
    the one to pass to the preview while the default is chosen. ownLabel is that
    face's name, for the caption.

    model   the wire:model path, e.g. "settings.theme_portfolio_font"
    value   the currently stored value, so the specimen is right on first paint
--}}
@props([
    'model',
    'value' => '',
    'ownFace' => null,
    'ownLabel' => null,
    'label' => 'Body Font',
    'hint' => null,
    'description' => null,
    'sample' => 'The quick brown fox jumps over the lazy dog.',
])

@php
    $options = \App\Support\ThemeFont::options();
    $stacks = \App\Support\ThemeFont::stacks();
    $current = \App\Support\ThemeFont::normalize($value);

    // Every webfont on offer that the panel's own bundle does not already
    // declare. Plus Jakarta Sans is in resources/css/fonts.css, which base.css
    // imports, so re-declaring it here would be a duplicate; the other two are
    // self-hosted for the storefront only and have to be said out loud.
    $specimenFaces = array_values(array_diff(
        array_keys(array_filter($options, fn ($option) => \App\Support\ThemeFont::facesFor($option) !== [], ARRAY_FILTER_USE_KEY)),
        [\App\Support\ThemeFont::PLUS_JAKARTA],
    ));

    // The same two rules the Alpine below applies, run once on the server so the
    // specimen is correct before Alpine boots. Kept in step deliberately: a
    // preview that starts on the wrong face and corrects itself is a flicker.
    $initialStack = $stacks[$current] ?? $stacks[$ownFace] ?? 'inherit';
    $initialCaption = $current === \App\Support\ThemeFont::THEME_DEFAULT
        ? $options[$current].(filled($ownLabel) ? ' — '.$ownLabel : '')
        : $options[$current];
@endphp

{{-- Declared here rather than in a layout because only the screen that offers
     the picker needs them, and a font that is not on offer is never downloaded
     on the other ninety-odd admin screens. --}}
<style>
    @foreach ($specimenFaces as $face)
        @foreach (\App\Support\ThemeFont::facesFor($face)[$face] as $source)
            {{-- The family name and the weight axis come from ThemeFont too. They
                 have to agree with the file that serves them, and nothing forces
                 them to: a family retyped here would be a download that is never
                 used, and an axis that claims one weight would render the next
                 one at the wrong weight. --}}
            @font-face {
                font-family: {{ \App\Support\ThemeFont::familiesFor($face)[$face] }};
                font-style: normal;
                font-weight: {{ \App\Support\ThemeFont::weightsFor($face)[$face] }};
                font-display: swap;
                src: url('{{ $source['url'] }}') format('woff2');
                unicode-range: {{ $source['unicodeRange'] }};
            }
        @endforeach
    @endforeach
</style>

@if ($preload = \App\Support\ThemeFont::preloadFor($current))
    {{-- Only the chosen face, and only its latin subset: the specimen is the
         first thing on this panel to need the file, so a swapped-in fallback
         would be visible for exactly the case the panel exists to answer. --}}
    <link rel="preload" href="{{ $preload }}" as="font" type="font/woff2" crossorigin>
@endif

<div
    x-data="{
        stacks: @js($stacks),
        labels: @js($options),
        own: @js($ownFace),
        ownLabel: @js($ownLabel),
        get chosen() { return $wire.{{ $model }} || '' },
        stack() { return this.stacks[this.chosen] || this.stacks[this.own] || 'inherit' },
        caption() {
            return this.chosen
                ? (this.labels[this.chosen] || this.chosen)
                : this.labels[''] + (this.ownLabel ? ' — ' + this.ownLabel : '');
        },
    }"
    {{ $attributes->class(['grid gap-5 xl:grid-cols-[minmax(0,19rem)_minmax(0,1fr)] xl:items-start']) }}>

    <div>
        <flux:field>
            <flux:label>{{ $label }}<x-field-hint :text="$hint" /></flux:label>
            <flux:select wire:model="{{ $model }}" class="w-full">
                @foreach ($options as $option => $optionLabel)
                    <flux:select.option value="{{ $option }}">{{ $optionLabel }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($description)
                <flux:description>{{ $description }}</flux:description>
            @endif
        </flux:field>
    </div>

    {{-- A type specimen rather than another preview of the panel: the size,
         the leading and the colour are the panel's, and only the family is the
         choice. --}}
    <figure class="overflow-hidden rounded-lg bg-zinc-50/70 dark:bg-zinc-800/40">
        <figcaption
            class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-4 py-2 dark:border-zinc-700/70">
            <span class="text-[11px] font-semibold tracking-wide text-zinc-400 uppercase">Preview</span>
            <span class="truncate text-xs font-medium text-zinc-600 dark:text-zinc-300" x-text="caption()">{{ $initialCaption }}</span>
        </figcaption>

        <div class="p-4" style="font-family: {{ $initialStack }}" x-bind:style="'font-family: ' + stack()">
            <p class="text-3xl leading-none font-semibold tracking-tight text-zinc-800 dark:text-zinc-100">Aa Bb Cc</p>
            <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $sample }}</p>
            <p class="mt-2 text-[11px] text-zinc-400">Handgloves 0123456789</p>
        </div>
    </figure>
</div>
