@props(['actions' => []])

@php
    // Full literal utility strings so Tailwind's scanner can see them (dynamic
    // "border-{$color}" concatenation would be invisible to the JIT compiler).
    $palettes = [
        'primary' => ['border' => 'border-primary text-primary hover:bg-primary hover:text-white', 'glow' => 'rgba(99,102,241,.35)', 'tooltipBg' => 'bg-primary', 'arrow' => 'border-t-primary', 'menuText' => 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700'],
        'secondary' => ['border' => 'border-secondary text-secondary hover:bg-secondary hover:text-white', 'glow' => 'rgba(139,92,246,.35)', 'tooltipBg' => 'bg-secondary', 'arrow' => 'border-t-secondary', 'menuText' => 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700'],
        'rose-500' => ['border' => 'border-rose-500 text-rose-500 hover:bg-rose-500 hover:text-white', 'glow' => 'rgba(225,29,72,.35)', 'tooltipBg' => 'bg-rose-500', 'arrow' => 'border-t-rose-500', 'menuText' => 'text-rose-600 hover:bg-rose-50'],
        'emerald-500' => ['border' => 'border-emerald-500 text-emerald-500 hover:bg-emerald-500 hover:text-white', 'glow' => 'rgba(16,185,129,.35)', 'tooltipBg' => 'bg-emerald-500', 'arrow' => 'border-t-emerald-500', 'menuText' => 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700'],
        'cyan-500' => ['border' => 'border-cyan-500 text-cyan-500 hover:bg-cyan-500 hover:text-white', 'glow' => 'rgba(8,145,178,.35)', 'tooltipBg' => 'bg-cyan-500', 'arrow' => 'border-t-cyan-500', 'menuText' => 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700'],
        'amber-500' => ['border' => 'border-amber-500 text-amber-500 hover:bg-amber-500 hover:text-white', 'glow' => 'rgba(245,158,11,.35)', 'tooltipBg' => 'bg-amber-500', 'arrow' => 'border-t-amber-500', 'menuText' => 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700'],
        'zinc-500' => ['border' => 'border-zinc-400 text-zinc-500 hover:bg-zinc-600 hover:text-white hover:border-zinc-600', 'glow' => 'rgba(82,82,91,.35)', 'tooltipBg' => 'bg-zinc-600', 'arrow' => 'border-t-zinc-600', 'menuText' => 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700'],
    ];

    // Exact SVG markup reused from the previous per-page inline buttons, so
    // switching to this shared component doesn't change how any icon looks.
    $icons = [
        'pencil' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" />',
        'squares' => '<rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />',
        'trash' => '<polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4h6v2" />',
        'grid-cross' => '<rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /><line x1="17.5" y1="14" x2="17.5" y2="21" /><line x1="14" y1="17.5" x2="21" y2="17.5" />',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />',
        'eye-slash' => '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" /><line x1="1" y1="1" x2="23" y2="23" />',
        'envelope' => '<rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />',
    ];

    $visible = collect($actions)->filter(fn ($action) => $action['visible'] ?? true)->values();
    $mode = \App\Models\Setting::get('admin_actions_display', 'inline');
@endphp

@if ($mode === 'dropdown')
    {{-- A shared Alpine store (not per-row local state) tracks which single row's
    menu is open, so opening one always closes any other — see
    resources/js/row-actions-store.js. The <tr> in each index.blade.php
    right-clicks by dispatching a real click at [data-actions-trigger] below,
    reusing this same toggle logic rather than duplicating it. --}}
    <div class="relative flex justify-center" x-data="{ uid: Math.random() }"
        @click.outside="if ($store.rowActions.openId === uid) $store.rowActions.openId = null">
        <button type="button" data-actions-trigger
            @click="$store.rowActions.openId = ($store.rowActions.openId === uid ? null : uid)"
            aria-label="Actions"
            class="inline-flex items-center justify-center w-7 h-7 rounded border border-zinc-200 text-zinc-500 hover:bg-zinc-100 transition-colors cursor-pointer">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="5" cy="12" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="19" cy="12" r="2" />
            </svg>
        </button>
        <div x-show="$store.rowActions.openId === uid" x-cloak x-transition.origin.top.right
            class="absolute top-full right-0 mt-1 w-40 bg-white rounded-lg border border-zinc-200 shadow-lg py-1 z-30 dark:bg-zinc-800 dark:border-zinc-700">
            @foreach ($visible as $action)
                @php $palette = $palettes[$action['color'] ?? 'primary'] ?? $palettes['primary']; @endphp
                @if ($action['disabled'] ?? false)
                    <span class="flex items-center gap-2 px-3 py-1.5 text-xs text-zinc-300 cursor-not-allowed">
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </span>
                @elseif (isset($action['href']))
                    <a href="{{ $action['href'] }}" wire:navigate @click="$store.rowActions.openId = null"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs {{ $palette['menuText'] }}">
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </a>
                @else
                    <button type="button" wire:click="{{ $action['wireClick'] }}" @click="$store.rowActions.openId = null"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-xs text-left cursor-pointer {{ $palette['menuText'] }}">
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </button>
                @endif
            @endforeach
        </div>
    </div>
@else
    <div class="flex items-center justify-center gap-1.5">
        @foreach ($visible as $action)
            @php $palette = $palettes[$action['color'] ?? 'primary'] ?? $palettes['primary']; @endphp
            <div class="relative group">
                @if ($action['disabled'] ?? false)
                    <span aria-label="{{ $action['label'] }}"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg border bg-zinc-50 text-zinc-300 cursor-not-allowed">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$action['icon']] !!}</svg>
                    </span>
                @elseif (isset($action['href']))
                    <a href="{{ $action['href'] }}" wire:navigate aria-label="{{ $action['label'] }}"
                        class="inline-flex items-center justify-center w-7 h-7 rounded border transition-all duration-150 {{ $palette['border'] }} hover:-translate-y-px"
                        style="box-shadow:none"
                        onmouseover="this.style.boxShadow='0 3px 8px {{ $palette['glow'] }}'"
                        onmouseout="this.style.boxShadow='none'">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$action['icon']] !!}</svg>
                    </a>
                @else
                    <button type="button" wire:click="{{ $action['wireClick'] }}" aria-label="{{ $action['label'] }}"
                        class="inline-flex items-center justify-center w-7 h-7 rounded border transition-all duration-150 {{ $palette['border'] }} hover:-translate-y-px cursor-pointer"
                        style="box-shadow:none"
                        onmouseover="this.style.boxShadow='0 3px 8px {{ $palette['glow'] }}'"
                        onmouseout="this.style.boxShadow='none'">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$action['icon']] !!}</svg>
                    </button>
                @endif
                @unless ($action['disabled'] ?? false)
                    <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium {{ $palette['tooltipBg'] }} text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                        {{ $action['label'] }}
                        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent {{ $palette['arrow'] }}"></span>
                    </span>
                @endunless
            </div>
        @endforeach
    </div>
@endif
