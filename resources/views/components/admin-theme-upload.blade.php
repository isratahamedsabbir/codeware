@props([
    'uploadKey',
    'type' => 'image',
    'label' => null,
    'hint' => null,
    'sizeHint' => null,
    'value' => '',
    'pending' => null,
])

{{--
    Direct file upload for a theme setting — the file is uploaded straight from
    the visitor's disk rather than picked from the Media Library, and stored
    under storage/app/public/theme-uploads/{setting key}/.

    Declared by a theme's settings.blade.php as
    <x-admin-theme-upload upload-key="theme_{slug}_*" type="image|pdf"
        :value="$settings[...]" :pending="$uploads[...]" />.
    App\Livewire\Admin\ThemeSettings\Index finds these by parsing the file for
    `upload-key` + `type` on this tag, validates the upload by type, and on save
    stores the new file, writes its URL into the setting and deletes the file it
    replaced — so only one file per setting is ever kept.
--}}
@php
    $value = (string) $value;
    $pending = $pending instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? $pending : null;
    $isImage = $type === 'image';
    $accept = $isImage ? 'image/jpeg,image/png,image/webp' : 'application/pdf';
    $note = $isImage ? 'JPG, PNG or WEBP · max 4MB' : 'PDF only · max 10MB';
    $inputId = 'tu-'.str_replace('_', '-', $uploadKey);

    $previewUrl = null;
    if ($pending && $isImage) {
        try {
            $previewUrl = $pending->temporaryUrl();
        } catch (\Throwable) {
            $previewUrl = null;
        }
    }
    if ($isImage) {
        $previewUrl ??= $value !== '' ? $value : null;
    }

    $fileName = $pending
        ? $pending->getClientOriginalName()
        : ($value !== '' ? basename(parse_url($value, PHP_URL_PATH) ?: $value) : null);
    $fileName = $fileName !== '' ? $fileName : null;
    $hasFile = $isImage ? $previewUrl !== null : $fileName !== null;
@endphp

<flux:field>
    @if ($label)
        <flux:label>
            {{ $label }}
            @if ($hint)
                <x-field-hint :text="$hint" />
            @endif
        </flux:label>
    @endif

    <div class="space-y-2" x-data="{ uploading: false }"
        x-on:livewire-upload-start="uploading = true"
        x-on:livewire-upload-finish="uploading = false"
        x-on:livewire-upload-error="uploading = false">

        @if ($isImage)
            {{-- The photo slot keeps the portrait shape whether or not a photo
                 is set, so the column does not jump when one is added. --}}
            @if ($previewUrl)
                <div class="relative w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ $previewUrl }}" alt="" class="aspect-4/5 w-full object-cover">
                    <button type="button" wire:click="clearThemeUpload('{{ $uploadKey }}')" title="Remove"
                        class="absolute top-2 right-2 flex size-7 items-center justify-center rounded-full bg-black/60 text-white shadow-sm backdrop-blur transition hover:bg-red-500">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            @else
                <label for="{{ $inputId }}"
                    class="flex aspect-4/5 w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-center text-zinc-400 transition hover:border-blue-400 hover:text-blue-600 dark:border-zinc-600 dark:bg-zinc-800/40">
                    <flux:icon.photo class="size-8" />
                    <span class="text-sm font-medium" x-show="! uploading">Upload photo</span>
                    <span class="text-sm font-medium" x-show="uploading" x-cloak>Uploading…</span>
                </label>
            @endif
        @elseif ($fileName)
            <div class="flex h-8 items-center gap-2 rounded-sm border border-zinc-200 bg-zinc-50 pr-1 pl-3 dark:border-zinc-700 dark:bg-zinc-800/40">
                <flux:icon.document-text class="size-4 shrink-0 text-red-500" />
                @if (! $pending && $value !== '')
                    <a href="{{ $value }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-700 hover:underline dark:text-zinc-200">{{ $fileName }}</a>
                @else
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $fileName }}</span>
                @endif
                <button type="button" wire:click="clearThemeUpload('{{ $uploadKey }}')" title="Remove"
                    class="flex size-6 shrink-0 items-center justify-center rounded-sm text-zinc-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        @endif

        @if (! $isImage || $previewUrl)
            <label for="{{ $inputId }}"
                class="flex h-8 cursor-pointer items-center justify-center gap-2 rounded-sm border border-dashed border-zinc-300 px-3 text-sm text-zinc-500 transition hover:border-blue-400 hover:text-blue-600 dark:border-zinc-600 dark:hover:bg-zinc-800">
                <flux:icon.arrow-up-tray class="size-4" />
                <span x-show="! uploading">{{ $hasFile ? 'Replace '.($isImage ? 'photo' : 'PDF') : 'Upload '.($isImage ? 'photo' : 'PDF') }}</span>
                <span x-show="uploading" x-cloak>Uploading…</span>
            </label>
        @endif
        <input id="{{ $inputId }}" type="file" class="hidden" accept="{{ $accept }}"
            wire:model="uploads.{{ $uploadKey }}">

        <p class="text-[11px] text-zinc-400">
            {{ $note }}@if ($sizeHint) · {{ $sizeHint }}@endif
            @if ($pending)
                · <span class="text-amber-600">Save to apply</span>
            @endif
        </p>

        <flux:error name="uploads.{{ $uploadKey }}" />
    </div>
</flux:field>
