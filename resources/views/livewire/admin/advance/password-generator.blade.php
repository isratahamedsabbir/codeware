<div class="max-w-2xl space-y-6">
    <flux:text class="text-zinc-500">
        Generate strong, random passwords using a cryptographically secure generator.
    </flux:text>

    <x-admin-section-card icon="key" title="Password Generator" icon-color="bg-amber-500/10 text-amber-600">

        <div x-data="{ copied: false, copy() { navigator.clipboard.writeText($refs.password.value); this.copied = true; setTimeout(() => this.copied = false, 1500); } }"
            class="flex items-center gap-2">
            <input x-ref="password" type="text" readonly value="{{ $password }}"
                class="flex-1 h-9 rounded border border-zinc-200 bg-zinc-50 px-3 font-mono text-sm text-zinc-800 dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-200" />
            <flux:button type="button" size="sm" variant="ghost" icon="clipboard-document" x-on:click="copy()">
                <span x-show="!copied">Copy</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </flux:button>
            <flux:button type="button" size="sm" variant="primary" icon="arrow-path" wire:click="generate">
                Generate
            </flux:button>
        </div>

        @if ($password)
            <div class="text-xs">
                <span class="text-zinc-400">Strength:</span>
                <span class="font-medium {{ match ($this->strength) {
                    'Strong' => 'text-green-600',
                    'Good' => 'text-amber-600',
                    default => 'text-rose-600',
                } }}">{{ $this->strength }}</span>
            </div>
        @endif

        <flux:field>
            <flux:label>Length ({{ $length }})</flux:label>
            <flux:input type="number" wire:model.live="length" min="6" max="128" />
            <flux:error name="length" />
        </flux:field>

        <div class="grid grid-cols-2 gap-3">
            <flux:checkbox wire:model.live="includeUpper" label="Uppercase (A-Z)" />
            <flux:checkbox wire:model.live="includeLower" label="Lowercase (a-z)" />
            <flux:checkbox wire:model.live="includeNumbers" label="Numbers (0-9)" />
            <flux:checkbox wire:model.live="includeSymbols" label="Symbols (!@#$...)" />
        </div>

        <flux:checkbox wire:model.live="excludeSimilar" label="Exclude similar characters (i, l, 1, L, o, 0, O)" />

        @error('options')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror

    </x-admin-section-card>
</div>
