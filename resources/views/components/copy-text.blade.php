@props(['text'])

<button type="button" x-data="{
    copied: false,
    copy(value) {
        // navigator.clipboard only exists in secure contexts (HTTPS or
        // localhost) — this admin often runs over plain HTTP on a .test
        // domain locally, where it's undefined and would throw silently.
        // Fall back to the classic hidden-textarea + execCommand trick there.
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value);
        } else {
            const ta = document.createElement('textarea');
            ta.value = value;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        }
        this.copied = true;
        setTimeout(() => this.copied = false, 1200);
    }
}"
    x-on:click.stop="copy(@js((string) $text))"
    {{ $attributes->class(['relative inline-flex items-center gap-1 text-left cursor-pointer hover:text-indigo-600 transition-colors min-w-0']) }}>
    <span class="truncate">{{ $slot }}</span>
    <span x-cloak x-show="copied" x-transition.opacity
        class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 rounded text-[11px] font-medium bg-zinc-800 text-white whitespace-nowrap z-10">
        Copied!
        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-800"></span>
    </span>
</button>
