<div class="max-w-[1600px] w-full mx-auto flex-1">

    <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">

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
                    <button type="button" @click="$refs.uploadInput.click()" x-show="! uploading"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                        Upload
                    </button>
                    <div x-show="uploading" x-cloak class="flex items-center gap-2">
                        <div class="w-24 h-1.5 rounded-full bg-zinc-100 overflow-hidden">
                            <div class="h-full bg-indigo-500 transition-all" :style="`width: ${progress}%`"></div>
                        </div>
                        <span class="text-[11px] text-zinc-500 tabular-nums" x-text="progress + '%'"></span>
                    </div>

                    <button wire:click="toggleSelectMode"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors {{ $selectMode ? 'border-indigo-300 bg-indigo-50 text-indigo-600' : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50' }}">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 11 12 14 20 6" />
                            <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9" />
                        </svg>
                        {{ $selectMode ? 'Cancel Select' : 'Select' }}
                    </button>

                    @if ($selectMode && count($checked))
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

                    <button wire:click="openCreateModal('folder')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8L9 4H4z" />
                            <line x1="12" y1="11" x2="12" y2="17" />
                            <line x1="9" y1="14" x2="15" y2="14" />
                        </svg>
                        New Folder
                    </button>
                    <button wire:click="openCreateModal('file')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="12" y1="12" x2="12" y2="17" />
                            <line x1="9.5" y1="14.5" x2="14.5" y2="14.5" />
                        </svg>
                        New File
                    </button>
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
                                class="admin-btn-save inline-flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-lg text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
                                @if ($selectMode)
                                    <input type="checkbox" wire:click.stop="toggleChecked(@js($entry['name']))"
                                        @checked(in_array($entry['name'], $checked, true))
                                        title="Select"
                                        class="absolute top-1.5 left-1.5 z-10 w-3.5 h-3.5 rounded border-zinc-300 text-indigo-500 focus:ring-indigo-400 cursor-pointer" />
                                @endif

                                {{-- 3-dot actions menu --}}
                                @if (! $entry['is_dir'] || $this->canManage)
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

                                <button wire:click="open(@js($entry['name']))" title="{{ $entry['name'] }}"
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
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
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
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
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
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
                    Rename
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Move / Copy modal --}}
    <flux:modal name="file-manager-transfer" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'file-manager-transfer') $flux.modal('file-manager-transfer').show()"
        x-on:close-modal.window="if ($event.detail.name === 'file-manager-transfer') $flux.modal('file-manager-transfer').close()">
        <div class="space-y-4">
            <flux:heading>{{ $transferMode === 'copy' ? 'Copy' : 'Move' }} "{{ $transferTarget }}"</flux:heading>
            <flux:field>
                <flux:label>Destination folder</flux:label>
                <flux:input wire:model="transferDestination" wire:keydown.enter="transferEntry"
                    placeholder="e.g. app/Models (leave blank for project root)" autofocus />
                <p class="text-xs text-zinc-400 mt-1">Path relative to the project root.</p>
                <flux:error name="transferDestination" />
            </flux:field>
            <div class="flex gap-2 pt-1">
                <button wire:click="transferEntry" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
                    {{ $transferMode === 'copy' ? 'Copy' : 'Move' }}
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
