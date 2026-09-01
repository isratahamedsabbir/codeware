@props(['options'])

<div {{ $attributes->class(['flex items-center gap-2 text-sm text-zinc-500 shrink-0']) }}>
    <select wire:model.live="perPage"
        class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white">
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>
</div>
