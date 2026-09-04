<div class="max-w-[1600px] w-full mx-auto flex-1">

    @if ($userId)
        @push('page-header-actions')
            <flux:button variant="ghost" size="sm" icon="identification" href="{{ route('admin.users.card', $userId) }}" target="_blank">
                View Card
            </flux:button>
            <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.users') }}" wire:navigate>
                Back
            </flux:button>
        @endpush
    @endif

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-lg shadow-sm p-6">

            {{-- Profile photo --}}
            <div class="flex items-center gap-5 mb-5">
                <div class="relative shrink-0">
                    <div class="size-20 rounded-full bg-gradient-to-br from-secondary to-primary flex items-center justify-center text-white text-xl font-bold shadow-md overflow-hidden">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="size-full object-cover">
                        @elseif ($existingPhotoPath && ! $removePhoto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingPhotoPath) }}" alt="{{ $name }}" class="size-full object-cover">
                        @else
                            @php
                                $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                            @endphp
                            {{ $initials ?: '?' }}
                        @endif
                    </div>
                </div>

                <div class="flex-1 space-y-2">
                    <flux:field>
                        <flux:label>Profile Photo</flux:label>
                        <flux:input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" />
                        <flux:error name="photo" />
                    </flux:field>
                    @if ($existingPhotoPath && ! $removePhoto && ! $photo)
                        <button type="button" wire:click="$set('removePhoto', true)"
                            class="text-xs text-red-600 hover:text-red-700 font-medium cursor-pointer">
                            Remove photo
                        </button>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Name <span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Full name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email <span class="text-red-500 ml-0.5">*</span></flux:label>
                    <flux:input wire:model="email" type="email" placeholder="user@example.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Password {{ $userId ? '(leave blank to keep current)' : '' }} @if (!$userId) <span class="text-red-500 ml-0.5">*</span>@endif</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="Min 8 characters" />
                    <flux:error name="password" />
                </flux:field>
            </div>

            {{-- Roles --}}
            <div class="mt-6">
                <h2 class="text-sm font-semibold text-zinc-900 mb-3">Roles</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @forelse ($roles as $role)
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group border border-zinc-200 rounded-lg px-4 py-3 hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors">
                            <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                class="mt-0.5 size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                            <div class="min-w-0">
                                <div class="text-sm text-zinc-800 group-hover:text-zinc-900 font-medium leading-snug">
                                    {{ $role->name }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $role->permissions_count }} permissions
                                </div>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500">No roles available yet. Create one first.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Settings
                </div>
                <div class="px-4 py-3">
                    <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                        <div>
                            <div class="text-sm font-medium text-zinc-800">Super Admin</div>
                            <div class="text-xs text-zinc-500 mt-0.5">Sets <span class="font-mono">is_admin</span> — full,
                                unconditional access to everything, regardless of assigned roles. The other two tiers
                                (Admin, Staff) are set via roles below.</div>
                        </div>
                        <input type="checkbox" wire:model="isAdmin"
                            class="size-4.5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                    </label>
                </div>
            </div>

            {{-- Signature --}}
            <div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-zinc-100 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    Signature
                </div>
                <div class="px-4 py-3 space-y-2"
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
            </div>

            {{-- Footer --}}
            <div class="flex justify-end items-center gap-3 border-t border-zinc-100 flex-wrap">
                <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="admin-btn-save inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                        <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                    </svg>
                    <span wire:loading.remove wire:target="save">{{ $userId ? 'Update User' : 'Create User' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>

        </div>

    </div>
</div>
