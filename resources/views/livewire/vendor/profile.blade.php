<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Profile photo, name & signature --}}
    <div class="lg:col-span-2 admin-card overflow-hidden self-start">
        <div class="px-5 py-3 border-b border-zinc-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-zinc-900">Profile Information</h2>
            <span class="text-xs text-zinc-400">Account photo and display name</span>
        </div>

        <form wire:submit="updateProfile" class="p-5 space-y-5">
            <div class="flex items-center gap-5">
                <div class="relative shrink-0">
                    <div
                        class="size-20 rounded-full bg-gradient-to-br from-secondary to-primary flex items-center justify-center text-white text-xl font-bold shadow-md overflow-hidden">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="size-full object-cover">
                        @elseif ($user->photo_url)
                            <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" class="size-full object-cover">
                        @else
                            {{ $user->initials() }}
                        @endif
                    </div>
                </div>

                <div class="flex-1 space-y-2">
                    <flux:field>
                        <flux:label>Profile Photo (square, max 2MB, JPG/PNG/WEBP)</flux:label>
                        <flux:input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp,image/avif" />
                        <flux:error name="photo" />
                    </flux:field>
                    @if ($user->photo)
                        <button type="button" wire:click="removePhoto"
                            class="text-xs text-red-600 hover:text-red-700 font-medium">
                            Remove photo
                        </button>
                    @endif
                </div>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" type="text" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email<x-field-hint text="Email cannot be changed here." /></flux:label>
                <flux:input :value="$email" type="email" readonly disabled />
            </flux:field>

            {{-- Signature --}}
            <flux:field>
                <flux:label>Signature<x-field-hint text="Draw or upload a signature image — saved with the button below." /></flux:label>
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
                            const canvas = this.$refs.canvas;
                            const rect = canvas.getBoundingClientRect();
                            const t = e.touches ? e.touches[0] : e;
                            const scaleX = canvas.width / rect.width;
                            const scaleY = canvas.height / rect.height;
                            return { x: (t.clientX - rect.left) * scaleX, y: (t.clientY - rect.top) * scaleY };
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
            </flux:field>

            <div class="flex items-center gap-3 pt-1">
                <flux:button size="sm" variant="primary" type="submit">Save Changes</flux:button>
            </div>
        </form>
    </div>

    {{-- Documents & password — stacked in their own column alongside the
         profile card, instead of one long centered column. --}}
    <div class="lg:col-span-1 space-y-5">

        {{-- Documents --}}
        <div class="admin-card overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h2 class="text-sm font-semibold text-zinc-900">Documents</h2>
                <p class="text-xs text-zinc-400 mt-0.5">ID, contract, certificate — PDF, DOC, or image files</p>
            </div>

            <div class="p-5 space-y-3">
                <div>
                    <input type="file" wire:model="newDocuments" multiple
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.avif"
                        class="block w-full text-xs text-zinc-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 cursor-pointer">
                    <flux:error name="newDocuments.*" />
                    @if ($newDocuments)
                        <button type="button" wire:click="uploadDocuments"
                            wire:loading.attr="disabled" wire:target="uploadDocuments"
                            class="mt-2 text-xs font-medium text-primary hover:opacity-80 cursor-pointer">
                            <span wire:loading.remove wire:target="uploadDocuments">Upload selected</span>
                            <span wire:loading wire:target="uploadDocuments">Uploading…</span>
                        </button>
                    @endif
                </div>

                <ul class="space-y-1.5">
                    @forelse ($documents as $document)
                        <li class="flex items-center justify-between gap-2 text-xs bg-zinc-50 border border-zinc-100 rounded-md px-2.5 py-1.5">
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file) }}" target="_blank"
                                class="min-w-0 truncate text-zinc-700 hover:text-primary font-medium" title="{{ $document->name }}">
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
        </div>

        {{-- Password --}}
        <div class="admin-card overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">Change Password</h2>
                <span class="text-xs text-zinc-400">Keep your account secure</span>
            </div>

            <form wire:submit="updatePassword" class="p-5 space-y-5">
                <flux:field>
                    <flux:label>Current Password</flux:label>
                    <flux:input wire:model="current_password" type="password" autocomplete="current-password" viewable />
                    <flux:error name="current_password" />
                </flux:field>

                <flux:field>
                    <flux:label>New Password</flux:label>
                    <flux:input wire:model="password" type="password" autocomplete="new-password" viewable />
                    <flux:error name="password" />
                </flux:field>

                <flux:field>
                    <flux:label>Confirm New Password</flux:label>
                    <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" viewable />
                    <flux:error name="password_confirmation" />
                </flux:field>

                <div class="flex items-center gap-3 pt-1">
                    <flux:button size="sm" variant="primary" type="submit">Update Password</flux:button>
                </div>
            </form>
        </div>

    </div>

</div>
