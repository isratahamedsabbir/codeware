<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.product-vendors') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-[5px] shadow-sm p-6 space-y-4">

            <flux:field>
                <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
                <flux:input wire:model="name" placeholder="e.g. Global Supplies Ltd." />
                <flux:error name="name" />
            </flux:field>

            <x-media-picker model="logo" label="Logo" hint="Square image works best" placeholder="Select vendor logo from library" mimes="jpg,jpeg,png,webp,svg" only-images dropzone />

            <flux:field>
                <flux:label>Address</flux:label>
                <flux:textarea wire:model="address" rows="3" placeholder="Vendor's business address" />
                <flux:error name="address" />
            </flux:field>

            {{-- Footer --}}
            <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <x-admin-save-button :label="$vendorId ? 'Update Vendor' : 'Create Vendor'" />
            </div>

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            {{-- Signature --}}
            <x-admin-section-card icon="pencil" title="Signature" icon-color="bg-indigo-500/10 text-indigo-600"
                body-class="px-4 py-3" description="Draw or upload the vendor's signature image.">
                <div class="space-y-2"
                    x-data="{
                        drawing: false,
                        ctx: null,
                        init() {
                            this.ctx = this.$refs.canvas.getContext('2d');
                            this.ctx.strokeStyle = '#1f2937';
                            this.ctx.lineWidth = 2;
                            this.ctx.lineJoin = 'round';
                            this.ctx.lineCap = 'round';
                            @if ($signature && ! str_starts_with($signature, 'data:'))
                                const img = new Image();
                                img.onload = () => this.ctx.drawImage(img, 0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                                img.src = {{ \Illuminate\Support\Js::from(\Illuminate\Support\Facades\Storage::disk('public')->url($signature)) }};
                            @endif
                        },
                        point(e) {
                            const rect = this.$refs.canvas.getBoundingClientRect();
                            const t = e.touches ? e.touches[0] : e;
                            return { x: t.clientX - rect.left, y: t.clientY - rect.top };
                        },
                        start(e) {
                            e.preventDefault();
                            this.drawing = true;
                            const p = this.point(e);
                            this.ctx.beginPath();
                            this.ctx.moveTo(p.x, p.y);
                        },
                        draw(e) {
                            if (! this.drawing) return;
                            e.preventDefault();
                            const p = this.point(e);
                            this.ctx.lineTo(p.x, p.y);
                            this.ctx.stroke();
                        },
                        stop() {
                            if (! this.drawing) return;
                            this.drawing = false;
                            $wire.signature = this.$refs.canvas.toDataURL('image/png');
                        },
                        clear() {
                            this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                            this.$refs.fileInput.value = '';
                            $wire.signature = null;
                        },
                        upload(e) {
                            const file = e.target.files[0];
                            if (! file) return;

                            const reader = new FileReader();
                            reader.onload = (ev) => {
                                const img = new Image();
                                img.onload = () => {
                                    const canvas = this.$refs.canvas;
                                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                                    const scale = Math.min(canvas.width / img.width, canvas.height / img.height, 1);
                                    const w = img.width * scale;
                                    const h = img.height * scale;
                                    this.ctx.drawImage(img, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h);
                                    $wire.signature = canvas.toDataURL('image/png');
                                };
                                img.src = ev.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    }">
                    <canvas x-ref="canvas" width="280" height="120"
                        @mousedown="start" @mousemove="draw" @mouseup="stop" @mouseleave="stop"
                        @touchstart="start" @touchmove="draw" @touchend="stop"
                        class="w-full border border-zinc-300 rounded-lg bg-white cursor-crosshair touch-none"></canvas>
                    <input type="file" x-ref="fileInput" accept="image/*" @change="upload" class="hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-zinc-500">Draw, or</p>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="$refs.fileInput.click()"
                                class="text-xs font-medium text-zinc-500 hover:text-zinc-800 transition-colors cursor-pointer">
                                Upload image
                            </button>
                            <button type="button" @click="clear"
                                class="text-xs font-medium text-zinc-500 hover:text-red-600 transition-colors cursor-pointer">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </x-admin-section-card>

            {{-- Documents --}}
            <x-admin-section-card icon="document-text" title="Documents" icon-color="bg-cyan-500/10 text-cyan-600"
                body-class="px-4 py-3" description="Trade license, contract, NID — PDF, DOC, or image files.">
                @if ($vendorId)
                    <div class="space-y-3">
                        <div>
                            <input type="file" wire:model="newDocuments" multiple
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                                class="block w-full text-xs text-zinc-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 cursor-pointer">
                            <flux:error name="newDocuments.*" />
                            @if ($newDocuments)
                                <button type="button" wire:click="uploadDocuments"
                                    wire:loading.attr="disabled" wire:target="uploadDocuments"
                                    class="mt-2 text-xs font-medium text-indigo-600 hover:text-indigo-700 cursor-pointer">
                                    <span wire:loading.remove wire:target="uploadDocuments">Upload selected</span>
                                    <span wire:loading wire:target="uploadDocuments">Uploading…</span>
                                </button>
                            @endif
                        </div>

                        <ul class="space-y-1.5">
                            @forelse ($documents as $document)
                                <li class="flex items-center justify-between gap-2 text-xs bg-zinc-50 border border-zinc-100 rounded-md px-2.5 py-1.5">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file) }}" target="_blank"
                                        class="min-w-0 truncate text-zinc-700 hover:text-indigo-600 font-medium" title="{{ $document->name }}">
                                        {{ $document->name }}
                                    </a>
                                    <button type="button" wire:click="deleteDocument({{ $document->id }})"
                                        wire:confirm="Delete this document?"
                                        class="shrink-0 text-zinc-400 hover:text-red-600 cursor-pointer">
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </li>
                            @empty
                                <p class="text-xs text-zinc-400 px-1">No documents uploaded yet.</p>
                            @endforelse
                        </ul>
                    </div>
                @else
                    <p class="text-xs text-zinc-400">Save the vendor first, then upload documents.</p>
                @endif
            </x-admin-section-card>

        </div>

    </div>
</div>
