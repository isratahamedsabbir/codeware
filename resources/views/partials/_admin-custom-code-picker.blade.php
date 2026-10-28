{{-- Sidebar card on the page / product / post / category forms: which Settings →
     Custom Code snippet this page loads, on top of the global code. Binds to
     $custom_code_id from App\Concerns\HasSeoFields. --}}
<x-admin-section-card variant="postbox" persist-key="custom-code" title="Custom Code" :collapsed="false"
    description="Head/body snippet loaded on this page, in addition to the global code.">
    @if ($codeOptions = \App\Support\CustomCode::options())
        <flux:field>
            <flux:label>Snippet</flux:label>
            <flux:select wire:model="custom_code_id" class="w-full">
                <flux:select.option value="">None</flux:select.option>
                @foreach ($codeOptions as $codeId => $codeName)
                    <flux:select.option value="{{ $codeId }}">{{ $codeName }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    @else
        <p class="text-sm text-zinc-500">
            No snippets yet. Add one in
            <a href="{{ route('admin.settings') }}?tab=custom-code" class="text-primary hover:underline">Settings → Custom Code</a>.
        </p>
    @endif
</x-admin-section-card>
