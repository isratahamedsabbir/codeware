<div class="max-w-[1600px] w-full mx-auto flex-1">

    <style>
        .jodit-fixed-wrap .jodit-container,
        .jodit-fixed-wrap .jodit-wysiwyg_wrap,
        .jodit-fixed-wrap .jodit-workplace,
        .jodit-fixed-wrap .jodit-wysiwyg {
            height: 180px !important;
            min-height: 180px !important;
            max-height: 180px !important;
            resize: none !important;
            overflow-y: auto !important;
        }

        .jodit-fixed-wrap .jodit-container {
            border-radius: 6px;
        }
    </style>

    <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.jobs') }}" wire:navigate class="mb-4 border-2">
        Back
    </flux:button>

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg border border-zinc-100 shadow-sm p-6">

            <h1 class="text-lg font-semibold text-zinc-900 mb-5 pb-4 border-b border-zinc-100">
                {{ $jobId ? 'Edit Job' : 'New Job' }}
            </h1>

            <div x-data="{ locale: 'en' }">

                {{-- Locale Tabs --}}
                <div class="flex gap-2 mb-4">
                    <button type="button"
                        :class="locale === 'en' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                        @click="locale='en'">EN</button>
                    <button type="button"
                        :class="locale === 'bn' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="px-3.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                        @click="locale='bn'">বাং</button>
                </div>

                {{-- English --}}
                <div x-show="locale==='en'" class="space-y-4">
                    <flux:field>
                        <flux:label>Title <span class="text-red-500 ml-0.5">*</span></flux:label>
                        <flux:input wire:model="title_en" placeholder="Job title in English" />
                        <flux:error name="title_en" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="auto-generated-from-title" />
                        <p class="text-xs text-zinc-400 mt-1">Leave blank to auto-generate from the English title</p>
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <div class="jodit-fixed-wrap">
                            <livewire:jodit-text-editor wire:model="description_en" :height="180" />
                        </div>
                    </flux:field>
                </div>

                {{-- Bengali --}}
                <div x-show="locale==='bn'" class="space-y-4">
                    <flux:field>
                        <flux:label>শিরোনাম <span class="text-red-500 ml-0.5">*</span></flux:label>
                        <flux:input wire:model="title_bn" placeholder="বাংলায় চাকরির শিরোনাম" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="auto-generated-from-title" />
                        <p class="text-xs text-zinc-400 mt-1">Leave blank to auto-generate from the English title</p>
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:field>
                        <flux:label>বিবরণ</flux:label>
                        <div class="jodit-fixed-wrap">
                            <livewire:jodit-text-editor wire:model="description_bn" :height="180" />
                        </div>
                    </flux:field>
                </div>

                {{-- Department & Position --}}
                <div class="mt-5 pt-4 border-t border-zinc-100">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Department</flux:label>
                            <flux:select wire:model="department_id">
                                <flux:select.option value="">— None —</flux:select.option>
                                @foreach ($this->departments as $dept)
                                    <flux:select.option value="{{ $dept->id }}">
                                        {{ $dept->getTranslation('name', 'en', false) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="department_id" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Position <span class="text-red-500 ml-0.5">*</span></flux:label>
                            <flux:input wire:model="position" placeholder="e.g. Senior Engineer" />
                            <flux:error name="position" />
                        </flux:field>
                    </div>
                </div>

                {{-- Vacancy, Deadline, Location --}}
                <div class="mt-5 pt-4 border-t border-zinc-100">
                    <div class="grid grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label>Vacancy <span class="text-red-500 ml-0.5">*</span></flux:label>
                            <flux:input type="number" wire:model="vacancy" min="1" />
                            <flux:error name="vacancy" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Deadline</flux:label>
                            <flux:input type="date" wire:model="deadline" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Location</flux:label>
                            <flux:input wire:model="location" placeholder="e.g. Dhaka, Bangladesh" />
                        </flux:field>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Status & Sort Order --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Job Settings
                </div>
                <div class="px-4 py-3 border-b border-zinc-50">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="inactive">Inactive</flux:select.option>
                            <flux:select.option value="active">Active</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
                <div class="px-4 py-3">
                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input type="number" wire:model="sort_order" min="0" />
                    </flux:field>
                </div>
            </div>

            {{-- Document --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Extra Information
                </div>
                <div class="px-4 py-4">
                    <x-media-picker model="document_file" label="" placeholder="Select PDF document from library"
                        :picker-id="$documentPickerId" />
                    <p class="text-[10px] text-zinc-400 mt-2 leading-relaxed">Upload a PDF with additional job details.
                    </p>
                </div>
            </div>

            <livewire:admin.media-library.picker-modal />

            {{-- Footer --}}
            <div class="flex justify-end items-center gap-3 border-t border-zinc-100 flex-wrap pt-4">
                <a href="{{ route('admin.jobs') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg border text-red-600 border-red-200 bg-white hover:bg-red-50 hover:border-red-400 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Cancel
                </a>
                <button wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors"
                    style="background:#28A745" onmouseover="this.style.background='#218838'"
                    onmouseout="this.style.background='#28A745'">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    {{ $jobId ? 'Update Job' : 'Create Job' }}
                </button>
            </div>

        </div>

    </div>
</div>
