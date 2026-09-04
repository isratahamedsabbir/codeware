<div class="mx-auto max-w-6xl space-y-4 p-6" x-data="{ selectedMedia: null }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Media Library</p>
            <h2 class="mt-1 text-sm font-black tracking-tight text-slate-900">Choose Media</h2>
        </div>
        <button type="button" wire:click="openUploadModal"
            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            Upload Files
        </button>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="$set('filterType', 'all')"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $filterType === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All</button>
                <button type="button" wire:click="$set('filterType', 'image')"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $filterType === 'image' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Images</button>
                <button type="button" wire:click="$set('filterType', 'document')"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $filterType === 'document' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Documents</button>
                <button type="button" wire:click="$set('filterType', 'video')"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $filterType === 'video' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Videos</button>
            </div>

            <div class="relative max-w-xs w-full">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search media..."
                    class="block h-8 w-full rounded border border-slate-300 text-sm font-bold focus:border-blue-500 focus:ring-blue-500 px-3" />
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3">
            <h3 class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-700">Library</h3>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $media->total() }}
                items</span>
        </div>

        @if ($media->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 p-5">
                @foreach ($media as $item)
                    <div wire:key="media-{{ $item->id }}" wire:click="selectMedia({{ $item->id }})"
                        class="group relative aspect-square rounded-xl border-2 {{ $selectedMediaId === $item->id ? 'border-blue-500 ring-2 ring-blue-200' : 'border-slate-200' }} overflow-hidden cursor-pointer bg-white transition-all hover:shadow-md">
                        @if ($item->isImage())
                            <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? ($item->title ?? '') }}"
                                class="h-full w-full object-cover" loading="lazy" />
                        @else
                            <div class="h-full w-full flex items-center justify-center bg-slate-100">
                                <svg class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                        @endif

                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                            <div class="absolute bottom-0 left-0 right-0 p-2">
                                <p class="text-[11px] font-bold text-white truncate">
                                    {{ $item->title ?? $item->original_filename }}</p>
                                <p class="text-[10px] text-slate-200">{{ $item->formatted_size }}</p>
                            </div>
                        </div>

                        @if ($selectedMediaId === $item->id)
                            <div class="absolute top-2 right-2">
                                <svg class="h-6 w-6 text-blue-500 drop-shadow" fill="currentColor" viewBox="0 0 20 20">
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
                <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-3 flex items-center justify-between">
                    <button wire:click="previousPage" wire:loading.attr="disabled"
                        class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50">Previous</button>
                    <span class="text-[11px] font-bold text-slate-500">Page {{ $media->currentPage() }} of
                        {{ $media->lastPage() }}</span>
                    <button wire:click="nextPage" wire:loading.attr="disabled"
                        class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-50">Next</button>
                </div>
            @endif
        @else
            <div class="p-12 text-center text-sm text-slate-500">No media found</div>
        @endif

        @if ($selectedMediaId)
            @php($selectedMedia = $media->firstWhere('id', $selectedMediaId))
            @if ($selectedMedia)
                <div
                    class="border-t border-slate-200 bg-slate-50 px-5 py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 my-3">
                        <div class="h-12 w-12 rounded-lg overflow-hidden bg-slate-200">
                            @if ($selectedMedia->isImage())
                                <img src="{{ $selectedMedia->url }}" alt=""
                                    class="h-full w-full object-cover" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-900 truncate">
                                {{ $selectedMedia->title ?? $selectedMedia->original_filename }}</p>
                            <p class="text-xs text-slate-500">{{ $selectedMedia->formatted_size }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="confirmSelection"
                            class="rounded-md bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">Use This Media</button>
                        <button type="button" wire:click="clearSelection"
                            class="px-3 py-2 text-slate-400 hover:text-slate-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </section>

    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4" @click.self="$wire.closeUploadModal()">
                <div class="flex items-center justify-between p-6 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Upload Files</h3>
                    <button wire:click="closeUploadModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div
                        class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-blue-500 transition-colors">
                        <input type="file" wire:key="uploadFiles-{{ $uploadIteration }}" wire:model="uploadFiles"
                            multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden"
                            id="file-upload" />
                        <label for="file-upload" class="cursor-pointer">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <p class="mt-4 text-sm font-bold text-slate-700">Drop files here or click to upload</p>
                            <p class="mt-1 text-xs text-slate-500">Supports: Images, Videos, Audio, PDFs (Max 10MB)</p>
                        </label>
                    </div>

                    @if ($uploadFiles)
                        <div class="mt-4 space-y-2">
                            @foreach ($uploadFiles as $file)
                                <div class="flex items-center gap-3 my-3 p-3 bg-slate-50 rounded-lg">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-slate-700 truncate flex-1">{{ $file->getClientOriginalName() }}</span>
                                    <span class="text-xs text-slate-500">{{ round($file->getSize() / 1024, 2) }}
                                        KB</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('uploadFiles.*')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div
                    class="flex items-center justify-end gap-3 p-6 border-t border-slate-200 bg-slate-50 rounded-b-2xl">
                    <button type="button" wire:click="closeUploadModal"
                        class="rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">Cancel</button>
                    <button type="button" wire:click="saveUploads"
                        class="rounded-md bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">Upload Files</button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    function postSelectedMedia(mediaData) {
        if (!mediaData) {
            return;
        }

        window.parent.postMessage({
            type: 'media-selected',
            pickerId: "{{ request('picker_id') }}",
            data: mediaData,
        }, '*');
    }

    window.addEventListener('media-selected', function(event) {
        const mediaData = event.detail?.media ?? event.detail;
        postSelectedMedia(mediaData);
    });

    window.Livewire?.on?.('media-selected', (event) => {
        const mediaData = event?.media ?? event?.[0]?.media ?? event?.[0] ?? event;
        postSelectedMedia(mediaData);
    });

    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'close-picker') {
            window.Livewire?.dispatch('closeUploadModal');
            window.Livewire?.dispatch('closeDetailsModal');
        }
    });
</script>
