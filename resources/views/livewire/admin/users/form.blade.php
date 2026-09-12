<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        @if ($userId)
            <flux:button variant="ghost" size="sm" icon="identification" href="{{ route('admin.users.card', $userId) }}" target="_blank">
                View Card
            </flux:button>
        @endif
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.users') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="flex gap-5 items-start">

        {{-- ── MAIN ── --}}
        <div class="flex-1 min-w-0 bg-white rounded-[5px] shadow-sm p-6">

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
                        <flux:label>Profile Photo (square, max 2MB, JPG/PNG/WEBP)</flux:label>
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

            {{-- Footer --}}
            <div class="-mx-6 -mb-6 mt-6 flex items-center gap-3 flex-wrap rounded-b-lg border-t border-zinc-100 bg-zinc-50/60 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                <x-admin-save-button :label="$userId ? 'Update User' : 'Create User'" />
            </div>

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="w-[320px] shrink-0 space-y-4">

            <x-admin-section-card icon="shield-check" title="Settings" body-class="px-4 py-3"
                description="Access level for this account.">
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
            </x-admin-section-card>

            {{-- Vendor Access --}}
            <x-admin-section-card icon="building-storefront" title="Vendor Access" icon-color="bg-amber-500/10 text-amber-600"
                body-class="px-4 py-3" description="Vendors this user can log in and see in the Vendor Portal.">
                <flux:checkbox.group wire:model="vendor_ids" class="flex-col items-stretch gap-0.5 max-h-56 overflow-y-auto">
                    @forelse ($vendors as $vendor)
                        <div class="rounded-md py-1 px-1 hover:bg-zinc-50 transition-colors">
                            <flux:checkbox value="{{ $vendor->id }}" label="{{ $vendor->name }}" />
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 px-2 py-1">No vendors yet.</p>
                    @endforelse
                </flux:checkbox.group>
                <flux:error name="vendor_ids" />
            </x-admin-section-card>

            {{-- Signature --}}
            <x-admin-section-card icon="pencil" title="Signature" icon-color="bg-indigo-500/10 text-indigo-600"
                body-class="px-4 py-3" description="Draw or upload a signature image.">
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

        </div>

    </div>
</div>
