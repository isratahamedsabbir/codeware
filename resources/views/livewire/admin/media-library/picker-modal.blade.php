<div x-data @open-media-picker.window="$wire.openPicker($event.detail.pickerId)"
    @keydown.escape.window="$wire.closePicker()">
    {{-- ============================================================
         MEDIA PICKER MODAL — WordPress-style two-panel layout
         Opened by:  window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { pickerId } }))
         Returns:    Livewire dispatches 'mediaPickerSelected' → browser window event
         ============================================================ --}}

    @if ($show)
        <div class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="relative flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden"
                style="width: 95vw; max-width: 1280px; height: 90vh;">

                {{-- ── Header ── --}}
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50 shrink-0">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Codeware</p>
                        <h2 class="text-lg font-black text-slate-900 leading-tight">Media Library</h2>
                    </div>
                    <button type="button" wire:click="closePicker"
                        class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- ── Tabs ── --}}
                <div class="flex border-b border-slate-200 bg-white shrink-0">
                    <button type="button" wire:click="switchTab('upload')"
                        class="px-6 py-3 text-xs font-bold uppercase tracking-widest transition-colors border-b-2 -mb-px
                        {{ $activeTab === 'upload' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            Upload Files
                        </span>
                    </button>
                    <button type="button" wire:click="switchTab('library')"
                        class="px-6 py-3 text-xs font-bold uppercase tracking-widest transition-colors border-b-2 -mb-px
                        {{ $activeTab === 'library' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            Media Library
                            <span
                                class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-500">{{ $total }}</span>
                        </span>
                    </button>
                </div>

                {{-- ══════════════════════ UPLOAD TAB ══════════════════════ --}}
                @if ($activeTab === 'upload')
                    <div class="flex flex-1 flex-col items-center justify-center p-10 overflow-y-auto">
                        <div x-data="{ dragging: false }" @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false" @drop.prevent="dragging = false"
                            :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-slate-300 bg-slate-50'"
                            class="w-full max-w-2xl rounded-2xl border-2 border-dashed p-16 text-center transition-colors cursor-pointer">
                            <input type="file" wire:key="picker-upload-{{ $uploadIteration }}"
                                wire:model="uploadFiles" multiple
                                accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden"
                                id="picker-file-input" />
                            <label for="picker-file-input" class="cursor-pointer">
                                <svg class="mx-auto h-14 w-14 text-slate-300" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                <p class="mt-5 text-base font-bold text-slate-700">Drop files here or click to upload
                                </p>
                                <p class="mt-2 text-sm text-slate-400">Supports: Images, Videos, Audio, PDFs &mdash; Max
                                    10 MB per file</p>
                            </label>
                        </div>

                        @if ($uploadFiles)
                            <div class="mt-6 w-full max-w-2xl space-y-2">
                                @foreach ($uploadFiles as $file)
                                    <div
                                        class="flex items-center gap-3 my-3 rounded-xl border border-slate-200 bg-white p-3">
                                        <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        <span
                                            class="flex-1 truncate text-sm font-medium text-slate-700">{{ $file->getClientOriginalName() }}</span>
                                        <span
                                            class="shrink-0 text-xs text-slate-400">{{ round($file->getSize() / 1024, 1) }}
                                            KB</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @error('uploadFiles.*')
                            <p class="mt-4 text-sm font-bold text-red-600">{{ $message }}</p>
                        @enderror

                        @if ($uploadFiles)
                            <div class="mt-6 flex items-center gap-3 my-3">
                                <button type="button" wire:click="switchTab('library')"
                                    class="rounded-md border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">Cancel</button>
                                <button type="button" wire:click="saveUploads"
                                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-5 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                    </svg>
                                    Upload {{ count($uploadFiles) }} {{ count($uploadFiles) === 1 ? 'File' : 'Files' }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ══════════════════════ LIBRARY TAB ══════════════════════ --}}
                @if ($activeTab === 'library')
                    <div class="flex flex-1 overflow-hidden">

                        {{-- ── Left panel: filters + grid ── --}}
                        <div class="flex flex-1 flex-col overflow-hidden border-r border-slate-100">

                            {{-- Filter bar --}}
                            <div
                                class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3 shrink-0">
                                <div class="flex items-center gap-1.5">
                                    @foreach (['all' => 'All', 'image' => 'Images', 'document' => 'Documents', 'video' => 'Videos'] as $type => $label)
                                        <button type="button" wire:click="$set('filterType', '{{ $type }}')"
                                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition-all
                                    {{ $filterType === $type ? 'bg-blue-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600' }}">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                <div class="relative ml-auto w-56">
                                    <div class="">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                    </div>
                                    <input type="text" wire:model.live.debounce.300ms="search"
                                        placeholder="Search…"
                                        class="block w-full rounded-lg border-slate-200 bg-white pl-9 text-xs focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                            </div>

                            {{-- Grid --}}
                            <div class="flex-1 overflow-y-auto p-4">
                                @if ($media->count() > 0)
                                    <div class="grid grid-cols-4 gap-3 sm:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8">
                                        @foreach ($media as $item)
                                            <button type="button" wire:key="picker-item-{{ $item->id }}"
                                                wire:click="selectMedia({{ $item->id }})"
                                                class="group relative aspect-square overflow-hidden rounded-xl border-2 transition-all focus:outline-none
                                    {{ $selectedMediaId === $item->id
                                        ? 'border-blue-500 ring-2 ring-blue-400/40 shadow-md shadow-blue-500/20'
                                        : 'border-slate-200 hover:border-blue-300 hover:shadow-sm' }}">
                                                @if ($item->isImage())
                                                    <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? '' }}"
                                                        class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                                        loading="lazy" />
                                                @elseif($item->isVideo())
                                                    <div
                                                        class="flex h-full w-full items-center justify-center bg-slate-900">
                                                        <svg class="h-8 w-8 text-white/70" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div
                                                        class="flex h-full w-full flex-col items-center justify-center gap-1 bg-slate-100 p-2">
                                                        <svg class="h-7 w-7 text-slate-400" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                        </svg>
                                                        <span
                                                            class="text-[9px] font-bold text-slate-400 uppercase truncate w-full text-center px-1">
                                                            {{ strtoupper(pathinfo($item->original_filename, PATHINFO_EXTENSION)) }}
                                                        </span>
                                                    </div>
                                                @endif

                                                {{-- Hover overlay with filename --}}
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                                                    <p
                                                        class="absolute bottom-0 left-0 right-0 truncate px-1.5 pb-1.5 text-[10px] font-bold text-white">
                                                        {{ $item->title ?? $item->original_filename }}
                                                    </p>
                                                </div>

                                                {{-- Selected check badge --}}
                                                @if ($selectedMediaId === $item->id)
                                                    <div
                                                        class="absolute right-1.5 top-1.5 rounded-full bg-blue-500 p-0.5 shadow">
                                                        <svg class="h-3.5 w-3.5 text-white" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex h-full flex-col items-center justify-center py-16 text-center">
                                        <svg class="h-16 w-16 text-slate-200" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        <p class="mt-4 text-sm font-bold text-slate-400">
                                            {{ $search ? 'No results for "' . $search . '"' : 'No media uploaded yet' }}
                                        </p>
                                        <button type="button" wire:click="switchTab('upload')"
                                            class="mt-4 text-xs font-bold text-blue-600 hover:underline">
                                            Upload your first file →
                                        </button>
                                    </div>
                                @endif
                            </div>

                            {{-- Pagination --}}
                            @if ($totalPages > 1)
                                <div
                                    class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-5 py-2.5 shrink-0">
                                    <button type="button" wire:click="previousPage" @disabled($page <= 1)
                                        class="rounded-lg px-3 py-1.5 text-xs font-bold transition-colors
                                {{ $page <= 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-100' }}">
                                        ← Prev
                                    </button>
                                    <span class="text-xs font-bold text-slate-500">
                                        Page {{ $page }} of {{ $totalPages }}
                                        <span class="text-slate-400 font-normal">({{ $total }} items)</span>
                                    </span>
                                    <button type="button" wire:click="nextPage({{ $totalPages }})"
                                        @disabled($page >= $totalPages)
                                        class="rounded-lg px-3 py-1.5 text-xs font-bold transition-colors
                                {{ $page >= $totalPages ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-100' }}">
                                        Next →
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- ── Right panel: attachment details ── --}}
                        <div class="w-72 shrink-0 overflow-y-auto bg-slate-50 xl:w-80">
                            @if ($selectedMedia)
                                <div class="flex h-full flex-col">
                                    {{-- Preview --}}
                                    <div class="bg-slate-900 p-4">
                                        @if ($selectedMedia->isImage())
                                            <img src="{{ $selectedMedia->url }}"
                                                alt="{{ $selectedMedia->alt_text ?? '' }}"
                                                class="mx-auto max-h-48 w-full rounded-lg object-contain" />
                                        @elseif($selectedMedia->isVideo())
                                            <div class="flex h-32 items-center justify-center rounded-lg bg-slate-800">
                                                <svg class="h-12 w-12 text-white/50" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="flex h-32 items-center justify-center rounded-lg bg-slate-800">
                                                <svg class="h-12 w-12 text-white/40" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Metadata --}}
                                    <div class="flex-1 space-y-5 p-5">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                Attachment Details</p>
                                            <p class="mt-1.5 break-all text-sm text-slate-900">
                                                {{ $selectedMedia->title ?? $selectedMedia->original_filename }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ $selectedMedia->original_filename }}</p>
                                        </div>

                                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                                            <div>
                                                <dt
                                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                    Uploaded</dt>
                                                <dd class="mt-0.5 text-xs font-semibold text-slate-700">
                                                    {{ $selectedMedia->created_at->format('M j, Y') }}</dd>
                                            </div>
                                            <div>
                                                <dt
                                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                    File Size</dt>
                                                <dd class="mt-0.5 text-xs font-semibold text-slate-700">
                                                    {{ $selectedMedia->formatted_size }}</dd>
                                            </div>
                                            @if ($selectedMedia->dimensions)
                                                <div>
                                                    <dt
                                                        class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                        Dimensions</dt>
                                                    <dd class="mt-0.5 text-xs font-semibold text-slate-700">
                                                        {{ $selectedMedia->dimensions['width'] }} ×
                                                        {{ $selectedMedia->dimensions['height'] }}
                                                    </dd>
                                                </div>
                                            @endif
                                            <div>
                                                <dt
                                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                    Type</dt>
                                                <dd class="mt-0.5 text-xs font-semibold text-slate-700 uppercase">
                                                    {{ strtoupper(pathinfo($selectedMedia->original_filename, PATHINFO_EXTENSION)) }}
                                                </dd>
                                            </div>
                                        </dl>

                                        @if ($selectedMedia->alt_text)
                                            <div>
                                                <dt
                                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                    Alt Text</dt>
                                                <dd class="mt-1 text-xs text-slate-600">{{ $selectedMedia->alt_text }}
                                                </dd>
                                            </div>
                                        @endif

                                        <div class="pt-3">
                                            <p
                                                class="mb-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                File URL</p>
                                            <input type="text" readonly value="{{ $selectedMedia->url }}"
                                                class="w-full rounded-lg border-slate-200 bg-white text-xs text-slate-500 focus:border-blue-500 focus:ring-blue-500"
                                                onclick="this.select()" />
                                        </div>
                                    </div>

                                    {{-- Select button --}}
                                    <div class="border-t border-slate-200 p-4">
                                        <button type="button" wire:click="confirmSelection"
                                            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700 active:scale-95 transition-all">
                                            Select this file
                                        </button>
                                        <button type="button" wire:click="selectMedia({{ $selectedMedia->id }})"
                                            class="mt-2 w-full rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                                            Deselect
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Empty state for details panel --}}
                                <div class="flex h-full flex-col items-center justify-center p-8 text-center">
                                    <div class="rounded-2xl bg-white/60 p-8">
                                        <svg class="mx-auto h-12 w-12 text-slate-200" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5M21 3.75H3.75" />
                                        </svg>
                                        <p class="mt-4 text-xs font-bold text-slate-400">Select a file to see its
                                            details</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                @endif

            </div>
        </div>
    @endif
</div>
