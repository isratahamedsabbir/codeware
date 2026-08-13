<div class="space-y-4">

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex justify-end">
        <button type="button" wire:click="openUploadModal"
            class="inline-flex items-center gap-2 rounded-md bg-blue-900 px-4 py-2.5 text-xs font-medium tracking-wide text-white transition-colors hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            Upload Files
        </button>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            {{-- Type filter tabs --}}
            <div class="flex items-center gap-1.5">
                @foreach (['all' => 'All', 'image' => 'Images', 'document' => 'Documents', 'video' => 'Videos'] as $value => $label)
                    <button type="button" wire:click="$set('filterType', '{{ $value }}')"
                        class="rounded-md px-3 py-1.5 text-xs font-medium tracking-wide transition-colors focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-1
                        {{ $filterType === $value ? 'bg-blue-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Search --}}
            <div class="relative w-full max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search media…"
                    class="block w-full rounded-md border border-slate-200 py-2.5 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/10" />
            </div>
        </div>
    </div>

    {{-- ─── Media Grid ──────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">

        {{-- Grid header --}}
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-3.5">
            <span class="text-[10px] font-medium uppercase tracking-widest text-slate-500">Library</span>
            <span class="text-xs font-medium text-slate-500">Total: {{ $media->total() }}</span>
        </div>

        @if ($media->count() > 0)
            <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($media as $item)
                    <div wire:key="media-{{ $item->id }}" wire:click="selectMedia({{ $item->id }})"
                        class="group relative aspect-square cursor-pointer overflow-hidden rounded-lg border transition-all
                        {{ $selectedMediaId === $item->id
                            ? 'border-blue-700 ring-2 ring-blue-700/20'
                            : 'border-slate-200 hover:border-slate-300 hover:shadow-sm' }}">

                        @if ($item->isImage())
                            <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? ($item->title ?? '') }}"
                                class="h-full w-full object-cover" />
                        @elseif($item->isVideo())
                            <div class="flex h-full w-full items-center justify-center bg-slate-900">
                                <svg class="h-10 w-10 text-white/80" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-slate-100">
                                <svg class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                        @endif

                        {{-- Hover overlay --}}
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                            <div class="absolute bottom-0 left-0 right-0 p-2">
                                <p class="truncate text-[10px] font-medium text-white">
                                    {{ $item->title ?? $item->original_filename }}</p>
                                <p class="text-[9px] text-slate-300">{{ $item->formatted_size }}</p>
                            </div>
                        </div>

                        {{-- Selected check --}}
                        @if ($selectedMediaId === $item->id)
                            <div class="absolute right-1.5 top-1.5">
                                <svg class="h-5 w-5 text-blue-700 drop-shadow" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($media->hasPages())
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-3">
                    {{ $media->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-14 w-14 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <p class="mt-4 text-xs font-medium uppercase tracking-widest text-slate-400">No media found</p>
            </div>
        @endif

        {{-- ─── Selection Action Bar ──────────────────────────────────────────── --}}
        @if ($selectedMediaId)
            @php($selectedMedia = $media->firstWhere('id', $selectedMediaId))
            @if ($selectedMedia)
                <span id="selected-media-data" data-url="{{ $selectedMedia->url }}"
                    data-title="{{ $selectedMedia->title ?? $selectedMedia->original_filename }}"
                    data-alt="{{ $selectedMedia->alt_text ?? '' }}" class="hidden"></span>

                <div
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    {{-- Preview --}}
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-md bg-slate-200">
                            @if ($selectedMedia->isImage())
                                <img src="{{ $selectedMedia->url }}" alt=""
                                    class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-800">
                                {{ $selectedMedia->title ?? $selectedMedia->original_filename }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $selectedMedia->formatted_size }} ·
                                {{ $selectedMedia->created_at->format('M d, Y') }}
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="viewDetails({{ $selectedMedia->id }})"
                            class="rounded-md border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium tracking-wide text-slate-600 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1">
                            Edit Details
                        </button>

                        <button type="button" x-data
                            @click="
                                const urlParams = new URLSearchParams(window.location.search);
                                const pickerId = urlParams.get('picker_id');
                                const isPicker = urlParams.get('picker') === '1';
                                if (!isPicker || !pickerId) { $wire.call('confirmSelection'); return; }
                                const el = document.getElementById('selected-media-data');
                                if (!el) return;
                                window.parent.postMessage({
                                    type: 'media-selected',
                                    pickerId: pickerId,
                                    data: { url: el.dataset.url, title: el.dataset.title, alt: el.dataset.alt }
                                }, '*');
                            "
                            class="rounded-md bg-blue-900 px-3.5 py-2 text-xs font-medium tracking-wide text-white transition-colors hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-1">
                            Use This Media
                        </button>

                        <button type="button" wire:click="deleteMedia({{ $selectedMedia->id }})"
                            wire:confirm="Are you sure?"
                            class="rounded-md px-3.5 py-2 text-xs font-medium tracking-wide text-red-600 transition-colors hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1">
                            Delete
                        </button>

                        <button type="button" wire:click="clearSelection"
                            class="rounded-md p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ─── Upload Modal ────────────────────────────────────────────────────── --}}
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                @click.away="$wire.closeUploadModal()">

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-medium text-slate-900">Upload Files</h3>
                    <button wire:click="closeUploadModal"
                        class="rounded p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <label for="file-upload"
                        class="flex cursor-pointer flex-col items-center rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center transition-colors hover:border-blue-700 hover:bg-blue-50/40">
                        <input type="file" wire:key="uploadFiles-{{ $uploadIteration }}" wire:model="uploadFiles"
                            multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden"
                            id="file-upload" />
                        <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <p class="mt-3 text-sm font-medium text-slate-700">Drop files here or click to browse</p>
                        <p class="mt-1 text-xs text-slate-400">Images, Videos, Audio, PDFs — max 10 MB</p>
                    </label>

                    @if ($uploadFiles)
                        <div class="mt-4 space-y-2">
                            @foreach ($uploadFiles as $file)
                                <div
                                    class="flex items-center gap-3 rounded-md border border-slate-100 bg-slate-50 px-3 py-2.5">
                                    <svg class="h-4 w-4 flex-shrink-0 text-slate-400" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span
                                        class="flex-1 truncate text-xs font-medium text-slate-700">{{ $file->getClientOriginalName() }}</span>
                                    <span class="text-[10px] text-slate-400">{{ round($file->getSize() / 1024, 2) }}
                                        KB</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('uploadFiles.*')
                        <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" wire:click="closeUploadModal"
                        class="rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-medium tracking-wide text-slate-600 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveUploads"
                        class="rounded-md bg-blue-900 px-4 py-2 text-xs font-medium tracking-wide text-white transition-colors hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-1">
                        Upload Files
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ─── Details Modal ───────────────────────────────────────────────────── --}}
    @if ($showDetailsModal && $editingMediaId)
        @php($mediaItem = \App\Models\MediaLibrary::find($editingMediaId))
        @if ($mediaItem)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
                <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
                    @click.away="$wire.closeDetailsModal()">

                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm font-medium text-slate-900">Edit Media Details</h3>
                        <button wire:click="closeDetailsModal"
                            class="rounded p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid gap-5 p-6 md:grid-cols-2">
                        {{-- Preview --}}
                        <div class="md:col-span-2">
                            <img src="{{ $mediaItem->url }}" alt=""
                                class="h-44 w-full rounded-lg object-cover bg-slate-100" />
                        </div>

                        {{-- Title --}}
                        <div class="space-y-1.5">
                            <label
                                class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Title</label>
                            <input wire:model="editTitle" type="text"
                                class="block w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/10" />
                        </div>

                        {{-- Alt Text --}}
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Alt
                                Text</label>
                            <input wire:model="editAltText" type="text" placeholder="For accessibility"
                                class="block w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/10" />
                        </div>

                        {{-- Caption --}}
                        <div class="md:col-span-2 space-y-1.5">
                            <label
                                class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Caption</label>
                            <input wire:model="editCaption" type="text"
                                class="block w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/10" />
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2 space-y-1.5">
                            <label
                                class="block text-[10px] font-medium uppercase tracking-widest text-slate-500">Description</label>
                            <textarea wire:model="editDescription" rows="3"
                                class="block w-full resize-none rounded-md border border-slate-200 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/10"></textarea>
                        </div>

                        {{-- Meta --}}
                        <div class="md:col-span-2 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-widest text-slate-500 mb-1">File
                                    Size</p>
                                <p class="text-sm text-slate-800">{{ $mediaItem->formatted_size }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-widest text-slate-500 mb-1">
                                    Dimensions</p>
                                <p class="text-sm text-slate-800">
                                    {{ $mediaItem->dimensions ? $mediaItem->dimensions['width'] . ' × ' . $mediaItem->dimensions['height'] : 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-widest text-slate-500 mb-1">
                                    Uploaded</p>
                                <p class="text-sm text-slate-800">{{ $mediaItem->created_at->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-widest text-slate-500 mb-1">Type
                                </p>
                                <p class="text-sm text-slate-800 uppercase">{{ $mediaItem->mime_type }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" wire:click="closeDetailsModal"
                            class="rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-medium tracking-wide text-slate-600 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1">
                            Cancel
                        </button>
                        <button type="button" wire:click="saveMediaDetails"
                            class="rounded-md bg-blue-900 px-4 py-2 text-xs font-medium tracking-wide text-white transition-colors hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-1">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

</div>
