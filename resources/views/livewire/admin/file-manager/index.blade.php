<div class="max-w-[1600px] w-full mx-auto flex-1">

    <div class="bg-white rounded-lg border border-zinc-100 shadow-sm">

        {{-- Breadcrumb / toolbar --}}
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-zinc-100 flex-wrap">
            <div class="flex items-center gap-1 text-sm flex-wrap min-w-0">
                <button wire:click="goTo('')"
                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors font-medium shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                    Project Root
                </button>
                @foreach ($this->breadcrumbs as $crumb)
                    <span class="text-zinc-300">/</span>
                    <button wire:click="goTo(@js($crumb['path']))"
                        class="px-2 py-1 rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors truncate max-w-[220px] shrink-0">
                        {{ $crumb['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-2 shrink-0"
                x-data="{ uploading: false, progress: 0 }"
                x-on:livewire-upload-start="uploading = true; progress = 0"
                x-on:livewire-upload-finish="uploading = false"
                x-on:livewire-upload-cancel="uploading = false"
                x-on:livewire-upload-error="uploading = false"
                x-on:livewire-upload-progress="progress = $event.detail.progress">
                <button type="button" wire:click="$refresh" wire:target="$refresh" title="Refresh"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                    <svg class="w-3.5 h-3.5" wire:loading.class="animate-spin" wire:target="$refresh"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10" />
                        <polyline points="1 20 1 14 7 14" />
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                    </svg>
                </button>
                @if ($this->canManage)
                    <input type="file" multiple x-ref="uploadInput" wire:model="uploads" class="hidden">
                    <div x-show="uploading" x-cloak class="flex items-center gap-2">
                        <div class="w-24 h-1.5 rounded-full bg-zinc-100 overflow-hidden">
                            <div class="h-full bg-indigo-500 transition-all" :style="`width: ${progress}%`"></div>
                        </div>
                        <span class="text-[11px] text-zinc-500 tabular-nums" x-text="progress + '%'"></span>
                    </div>

                    @if (count($checked))
                        <button wire:click="clearChecked"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Clear ({{ count($checked) }})
                        </button>
                        @if ($this->canManage)
                            <button wire:click="openTransferModalSelected('move')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 3H4a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-9" />
                                    <path d="M13 11l9-9" />
                                    <path d="M17 2h5v5" />
                                </svg>
                                Move Selected ({{ count($checked) }})
                            </button>
                            <button wire:click="openTransferModalSelected('copy')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" />
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                </svg>
                                Copy Selected ({{ count($checked) }})
                            </button>
                        @endif
                        <a href="{{ route('admin.file-manager.download-zip', ['path' => $path, 'names' => $checked]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Download Selected ({{ count($checked) }})
                        </a>
                        <button wire:click="zipSelected"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 8v13H3V8" />
                                <path d="M1 3h22v5H1z" />
                                <line x1="10" y1="12" x2="14" y2="12" />
                            </svg>
                            Zip Selected ({{ count($checked) }})
                        </button>
                        <button wire:click="confirmDeleteSelected"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                <path d="M10 11v6" />
                                <path d="M14 11v6" />
                                <path d="M9 6V4h6v2" />
                            </svg>
                            Delete Selected ({{ count($checked) }})
                        </button>
                    @endif

                    <div class="relative" x-data="{ menuOpen: false }" @click.outside="menuOpen = false">
                        <button type="button" @click="menuOpen = !menuOpen" title="New"
                            :class="menuOpen ? 'border-zinc-300 bg-zinc-50' : 'border-zinc-200'"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border text-zinc-600 hover:bg-zinc-50 transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="5" r="1.6" />
                                <circle cx="12" cy="12" r="1.6" />
                                <circle cx="12" cy="19" r="1.6" />
                            </svg>
                        </button>

                        <div x-show="menuOpen" x-cloak x-transition.origin.top.right
                            class="absolute right-0 mt-1 w-36 bg-white rounded-lg border border-zinc-200 shadow-lg py-1 z-30 text-left">
                            <button type="button" @click="menuOpen = false; $refs.uploadInput.click()"
                                class="w-full flex items-center gap-2 text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                Upload
                            </button>
                            <button type="button" @click="menuOpen = false" wire:click="openCreateModal('folder')"
                                class="w-full flex items-center gap-2 text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8L9 4H4z" />
                                    <line x1="12" y1="11" x2="12" y2="17" />
                                    <line x1="9" y1="14" x2="15" y2="14" />
                                </svg>
                                New Folder
                            </button>
                            <button type="button" @click="menuOpen = false" wire:click="openCreateModal('file')"
                                class="w-full flex items-center gap-2 text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="12" y1="12" x2="12" y2="17" />
                                    <line x1="9.5" y1="14.5" x2="14.5" y2="14.5" />
                                </svg>
                                New File
                            </button>
                        </div>
                    </div>
                @endif
                @if ($path !== '')
                    <button wire:click="up"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="19" x2="12" y2="5" />
                            <polyline points="5 12 12 5 19 12" />
                        </svg>
                        Up
                    </button>
                @endif
            </div>
        </div>

        @if ($errors->has('uploads.*'))
            <div class="px-6 py-2 bg-red-50 border-b border-red-100 text-xs text-red-600 space-y-0.5">
                @foreach ($errors->get('uploads.*') as $fieldErrors)
                    @foreach ($fieldErrors as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endforeach
            </div>
        @endif

        @if ($selected)
            {{-- ── Preview / editor panel ── --}}
            <div class="p-6 space-y-4" x-data="{ dirty: false }" x-on:file-manager-saved.window="dirty = false">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-sm font-semibold text-zinc-800 font-mono truncate">{{ $selected }}</p>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.file-manager.raw', ['path' => $selected, 'download' => 1]) }}"
                            class="inline-flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                            Download
                        </a>
                        @if ($editable && $this->canManage)
                            <button wire:click="saveFile" :disabled="! dirty" wire:loading.attr="disabled"
                                class="admin-btn-save inline-flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-lg text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Save
                            </button>
                        @endif
                        <button wire:click="closePreview"
                            class="inline-flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                            Close
                        </button>
                    </div>
                </div>

                @if ($kind === 'image')
                    <div class="flex items-center justify-center bg-zinc-50 rounded-lg border border-zinc-100 p-4">
                        <img src="{{ route('admin.file-manager.raw', ['path' => $selected]) }}"
                            class="max-h-[70vh] max-w-full object-contain rounded" alt="{{ $selected }}">
                    </div>
                @elseif ($kind === 'video')
                    <div class="flex items-center justify-center bg-black rounded-lg overflow-hidden">
                        <video controls class="max-h-[70vh] w-full">
                            <source src="{{ route('admin.file-manager.raw', ['path' => $selected]) }}">
                            Your browser doesn't support video playback.
                        </video>
                    </div>
                @elseif ($kind === 'text')
                    <textarea wire:model="editingContent" x-on:input="dirty = true" rows="26" spellcheck="false"
                        @if (! $this->canManage) readonly @endif
                        class="w-full font-mono text-xs leading-relaxed border border-zinc-200 rounded-lg p-3 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                        style="tab-size: 4; white-space: pre; overflow-wrap: normal; overflow-x: auto;"></textarea>
                @elseif ($kind === 'too-large')
                    <p class="text-sm text-zinc-500 py-10 text-center">This file is larger than 10&nbsp;MB and can't
                        be opened in the editor. Use Download to view it instead.</p>
                @else
                    <p class="text-sm text-zinc-500 py-10 text-center">This file type can't be previewed.</p>
                @endif
            </div>
        @else
            {{-- ── Directory listing ── --}}
            <div class="p-4">
                @if ($this->entries->isEmpty())
                    <p class="text-sm text-zinc-400 text-center py-16">This folder is empty.</p>
                @else
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-1.5">
                        @foreach ($this->entries as $entry)
                            @php $entryPath = ($path === '' ? '' : $path.'/').$entry['name']; @endphp
                            <div class="relative group">
                                @if ($this->canManage)
                                    <input type="checkbox" wire:click.stop="toggleChecked(@js($entry['name']))"
                                        @checked(in_array($entry['name'], $checked, true))
                                        title="Select"
                                        class="absolute top-1.5 left-1.5 z-10 w-3.5 h-3.5 rounded border-zinc-300 text-indigo-500 focus:ring-indigo-400 cursor-pointer transition-opacity {{ count($checked) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 focus:opacity-100' }}" />
                                @endif

                                {{-- 3-dot actions menu — hidden while anything is selected, since bulk actions take over --}}
                                @if (count($checked) === 0 && (! $entry['is_dir'] || $this->canManage))
                                <div class="absolute top-1 right-1 z-20" x-data="{ menuOpen: false }"
                                    @click.outside="menuOpen = false">
                                    <button type="button" @click="menuOpen = !menuOpen" title="Actions"
                                        :class="menuOpen ? 'opacity-100 border-zinc-300' : 'opacity-0 group-hover:opacity-100'"
                                        class="w-5 h-5 flex items-center justify-center rounded bg-white border border-zinc-200 text-zinc-500 hover:text-zinc-900 hover:border-zinc-300 transition-opacity">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="1.6" />
                                            <circle cx="12" cy="12" r="1.6" />
                                            <circle cx="12" cy="19" r="1.6" />
                                        </svg>
                                    </button>

                                    <div x-show="menuOpen" x-cloak x-transition.origin.top.right
                                        class="absolute right-0 mt-1 w-32 bg-white rounded-lg border border-zinc-200 shadow-lg py-1 z-30 text-left">
                                        @if (! $entry['is_dir'])
                                            <a href="{{ route('admin.file-manager.raw', ['path' => $entryPath, 'download' => 1]) }}"
                                                class="block px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                                Download
                                            </a>
                                        @endif
                                        @if ($this->canManage)
                                            @if ($entry['ext'] === 'zip')
                                                <button @click="menuOpen = false"
                                                    wire:click="extractZip(@js($entry['name']))"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                                    Extract
                                                </button>
                                            @endif
                                            @if ($entry['is_dir'])
                                                <button @click="menuOpen = false"
                                                    wire:click="openComposeModal(@js($entry['name']))"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                                    Compose
                                                </button>
                                            @endif
                                            <button @click="menuOpen = false"
                                                wire:click="openRenameModal(@js($entry['name']))"
                                                class="w-full text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                                Rename
                                            </button>
                                            <button @click="menuOpen = false"
                                                wire:click="openTransferModal('copy', @js($entry['name']))"
                                                class="w-full text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                                Copy
                                            </button>
                                            <button @click="menuOpen = false"
                                                wire:click="openTransferModal('move', @js($entry['name']))"
                                                class="w-full text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">
                                                Move
                                            </button>
                                            <button @click="menuOpen = false"
                                                wire:click="confirmDelete(@js($entry['name']))"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 border-t border-zinc-100 mt-1 pt-1.5">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <button wire:dblclick="open(@js($entry['name']))" title="{{ $entry['name'] }}"
                                    class="w-full flex flex-col items-center gap-1.5 p-3 rounded-lg border border-transparent hover:border-zinc-200 hover:bg-zinc-50 transition-colors text-center">
                                    <div class="w-9 h-9 flex items-center justify-center shrink-0">
                                        @if ($entry['is_dir'])
                                            <svg class="w-8 h-8 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8L9 4H4z" />
                                            </svg>
                                        @elseif (in_array($entry['ext'], ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif']))
                                            <svg class="w-7 h-7 text-emerald-400" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.5">
                                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                                <circle cx="8.5" cy="8.5" r="1.5" />
                                                <path d="M21 15l-5-5L5 21" />
                                            </svg>
                                        @elseif (in_array($entry['ext'], ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v']))
                                            <svg class="w-7 h-7 text-rose-400" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.5">
                                                <rect x="2" y="4" width="15" height="16" rx="2" />
                                                <path d="M17 8l5-3v14l-5-3" />
                                            </svg>
                                        @elseif ($entry['ext'] === 'zip')
                                            <svg class="w-7 h-7 text-violet-400" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.5">
                                                <path d="M21 8v13H3V8" />
                                                <path d="M1 3h22v5H1z" />
                                                <line x1="10" y1="12" x2="14" y2="12" />
                                            </svg>
                                        @else
                                            <svg class="w-7 h-7 text-zinc-400" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.5">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <polyline points="14 2 14 8 20 8" />
                                            </svg>
                                        @endif
                                    </div>
                                    <span class="text-[11px] text-zinc-600 truncate w-full leading-tight">
                                        {{ $entry['name'] }}
                                    </span>
                                    @if (! $entry['is_dir'] && $entry['size'] !== null)
                                        <span class="text-[10px] text-zinc-400">
                                            {{ \Illuminate\Support\Number::fileSize($entry['size']) }}
                                        </span>
                                    @endif
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>

    {{-- Create file/folder modal --}}
    <flux:modal name="file-manager-create" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'file-manager-create') $flux.modal('file-manager-create').show()"
        x-on:close-modal.window="if ($event.detail.name === 'file-manager-create') $flux.modal('file-manager-create').close()">
        <div class="space-y-4">
            <flux:heading>{{ $createType === 'folder' ? 'New Folder' : 'New File' }}</flux:heading>
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="newName" wire:keydown.enter="createEntry"
                    placeholder="{{ $createType === 'folder' ? 'my-folder' : 'notes.txt' }}" autofocus />
                <flux:error name="newName" />
            </flux:field>
            <div class="flex gap-2 pt-1">
                <button wire:click="createEntry" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    Create
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Compose modal — write a new file's content and save it in one step --}}
    <flux:modal name="file-manager-compose" class="md:w-xl"
        x-on:open-modal.window="if ($event.detail.name === 'file-manager-compose') $flux.modal('file-manager-compose').show()"
        x-on:close-modal.window="if ($event.detail.name === 'file-manager-compose') $flux.modal('file-manager-compose').close()">
        <div class="space-y-4">
            <flux:heading>
                Compose{{ $composeFolder ? ' in "'.$composeFolder.'"' : '' }}
            </flux:heading>
            <flux:field>
                <flux:label>File name</flux:label>
                <flux:input wire:model="composeName" placeholder="notes.txt" autofocus />
                <flux:error name="composeName" />
            </flux:field>
            <flux:field>
                <flux:label>Content</flux:label>
                <textarea wire:model="composeContent" rows="12" spellcheck="false"
                    class="w-full font-mono text-xs leading-relaxed border border-zinc-200 rounded-lg p-3 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                    style="tab-size: 4; white-space: pre; overflow-wrap: normal; overflow-x: auto;"></textarea>
            </flux:field>
            <div class="flex gap-2 pt-1">
                <button wire:click="composeFile" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    Create
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Rename modal --}}
    <flux:modal name="file-manager-rename" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'file-manager-rename') $flux.modal('file-manager-rename').show()"
        x-on:close-modal.window="if ($event.detail.name === 'file-manager-rename') $flux.modal('file-manager-rename').close()">
        <div class="space-y-4">
            <flux:heading>Rename</flux:heading>
            <flux:field>
                <flux:label>New name</flux:label>
                <flux:input wire:model="renameNewName" wire:keydown.enter="renameEntry" autofocus />
                <flux:error name="renameNewName" />
            </flux:field>
            <div class="flex gap-2 pt-1">
                <button wire:click="renameEntry" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    Rename
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Move / Copy modal --}}
    <flux:modal name="file-manager-transfer" class="md:w-104"
        x-on:open-modal.window="if ($event.detail.name === 'file-manager-transfer') $flux.modal('file-manager-transfer').show()"
        x-on:close-modal.window="if ($event.detail.name === 'file-manager-transfer') $flux.modal('file-manager-transfer').close()">
        <div class="space-y-4">
            <flux:heading>
                {{ $transferMode === 'copy' ? 'Copy' : 'Move' }}
                {{ $transferringSelected ? count($checked).' item(s)' : '"'.$transferTarget.'"' }}
            </flux:heading>

            <div>
                <flux:label>Destination folder</flux:label>

                <div class="flex items-center gap-1 mt-1.5 mb-2">
                    <button type="button" wire:click="transferUp" title="Up"
                        class="inline-flex items-center justify-center w-6 h-6 shrink-0 rounded border border-zinc-200 text-zinc-500 hover:bg-zinc-50 transition-colors disabled:opacity-40"
                        @disabled($transferDestination === '')>
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="19" x2="12" y2="5" />
                            <polyline points="5 12 12 5 19 12" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-1 text-xs flex-wrap min-w-0 text-zinc-500">
                        <button type="button" wire:click="transferGoTo('')"
                            class="px-1.5 py-0.5 rounded hover:bg-zinc-100 hover:text-zinc-900 transition-colors {{ $transferDestination === '' ? 'font-semibold text-zinc-900' : '' }}">
                            Project Root
                        </button>
                        @foreach ($this->transferBreadcrumbs as $crumb)
                            <span class="text-zinc-300">/</span>
                            <button type="button" wire:click="transferGoTo(@js($crumb['path']))"
                                class="px-1.5 py-0.5 rounded hover:bg-zinc-100 hover:text-zinc-900 transition-colors truncate max-w-27.5 {{ $crumb['path'] === $transferDestination ? 'font-semibold text-zinc-900' : '' }}">
                                {{ $crumb['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="border border-zinc-200 rounded-lg h-44 overflow-y-auto divide-y divide-zinc-50">
                    @forelse ($this->transferBrowseEntries as $folder)
                        <button type="button" wire:click="transferBrowseInto(@js($folder))"
                            class="w-full flex items-center gap-2 text-left px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50 transition-colors">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8L9 4H4z" />
                            </svg>
                            <span class="truncate">{{ $folder }}</span>
                        </button>
                    @empty
                        <p class="text-xs text-zinc-400 text-center py-6">No subfolders here.</p>
                    @endforelse
                </div>

                <flux:input wire:model.live.debounce.400ms="transferDestination" wire:keydown.enter="transferEntry"
                    placeholder="e.g. app/Models (leave blank for project root)" class="mt-2" />
                <p class="text-xs text-zinc-400 mt-1">Click a folder above to browse into it, or type a path
                    directly.</p>
                <flux:error name="transferDestination" />
            </div>

            <div class="flex gap-2 pt-1">
                <button wire:click="transferEntry" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    {{ $transferMode === 'copy' ? 'Copy' : 'Move' }} here
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete confirmation modal --}}
    <flux:modal name="file-manager-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'file-manager-delete') $flux.modal('file-manager-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'file-manager-delete') $flux.modal('file-manager-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>
                    @if ($deletingSelected)
                        Delete {{ count($checked) }} selected item(s)?
                    @else
                        Delete "{{ $deleteTarget }}"?
                    @endif
                </flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">This action cannot be undone. If it's a folder, everything inside
                it will be deleted too.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="{{ $deletingSelected ? 'deleteSelected' : 'deleteEntry' }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    Delete
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
