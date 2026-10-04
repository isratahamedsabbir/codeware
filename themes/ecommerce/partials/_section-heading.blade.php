<div class="mb-8">
    <div class="mb-2 flex items-center justify-center gap-3">
        <span class="relative h-1 w-[45px] rounded-full bg-brand">
            <span class="absolute -left-1 -top-1 h-3 w-3 rounded-sm bg-brand"></span>
        </span>
        <h2 class="text-center text-[20px] font-bold uppercase tracking-wide text-sf-text md:text-[25px]">{{ $title }}</h2>
        <span class="relative h-1 w-[45px] rounded-full bg-brand">
            <span class="absolute -right-1 -top-1 h-3 w-3 rounded-sm bg-brand"></span>
        </span>
    </div>
    @if (filled($subtitle ?? null))
        <p class="text-center text-sm text-gray-600 md:text-base">{{ $subtitle }}</p>
    @endif
</div>