{{--
    Section navigation for a theme's settings form — the vertical counterpart of
    the underline tab strip these forms used to carry.

    A tab strip lives at the top of a long form and scrolls away with it, so by
    the time you are halfway down the sixth section there is no sign of the five
    you have not seen. This is a list instead: it holds its place while the
    panel changes and the active entry stays marked, so the screen always says
    where you are and what is left without scrolling back up.

    Expects an Alpine `tab` string and an `open(name)` method in the parent
    scope — the same pair the panels already read through
    `x-show="tab === '…'"`, so the nav and the panels cannot disagree about
    which section is open. The parent is what owns that state (it is also what
    remembers it in localStorage); this component only drives it.

    Alpine scopes follow the DOM, not Blade, so the buttons reach the parent
    `x-data` even though this renders as a separate Blade component.

    Stays a row on small screens (a vertical list would push the form off the
    screen) and becomes the sidebar from `lg` up, where there is room beside it.

    tabs: [key => [label, icon]]
--}}
@props([
    'tabs' => [],
    'class' => '',
])

<nav role="tablist" aria-orientation="vertical"
    class="flex shrink-0 gap-1 overflow-x-auto lg:max-h-[calc(100vh-7rem)] lg:flex-col lg:overflow-x-visible lg:overflow-y-auto {{ $class }}">

    @foreach ($tabs as $tabKey => $tab)
        @php
            [$tabLabel, $tabIcon] = $tab;
        @endphp

        <button type="button" role="tab" @click="open('{{ $tabKey }}')"
            :aria-selected="tab === '{{ $tabKey }}'"
            :class="tab === '{{ $tabKey }}'
                ? 'bg-primary/10 text-primary'
                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'"
            class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap transition-colors lg:w-full lg:whitespace-normal">

            <span :class="tab === '{{ $tabKey }}'
                    ? 'bg-primary text-white'
                    : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'"
                class="flex size-7 shrink-0 items-center justify-center rounded-md transition-colors">
                <flux:icon :name="$tabIcon" variant="mini" class="size-4" />
            </span>

            <span class="min-w-0 truncate lg:whitespace-normal">{{ $tabLabel }}</span>
        </button>
    @endforeach
</nav>
