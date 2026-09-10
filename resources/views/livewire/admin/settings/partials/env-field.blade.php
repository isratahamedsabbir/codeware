{{-- One .env-backed field, shared by every card on the Env tab so the Google/Facebook
     Login cards (split out of the generic group loop in index.blade.php so each can sit
     next to its own "Where to get these" guide) render identically to the rest. --}}
<flux:field>
    <flux:label>{{ __($meta['label']) }}</flux:label>
    @if ($meta['type'] === 'boolean')
        <select wire:model="env.{{ $key }}"
            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
            <option value="true">{{ __('True') }}</option>
            <option value="false">{{ __('False') }}</option>
        </select>
    @elseif ($meta['type'] === 'select')
        <select wire:model="env.{{ $key }}"
            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
            @foreach ($meta['options'] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    @elseif ($meta['type'] === 'password')
        <flux:input type="password" wire:model="env.{{ $key }}" />
    @else
        <flux:input wire:model="env.{{ $key }}" />
    @endif
    <flux:error name="env.{{ $key }}" />
</flux:field>
