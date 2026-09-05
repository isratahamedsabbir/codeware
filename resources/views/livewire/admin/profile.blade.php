<div class="max-w-3xl mx-auto">
    {{-- Profile photo & name --}}
    <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm overflow-hidden mb-5">
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
                        <flux:label>Profile Photo</flux:label>
                        <flux:input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" />
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
                <flux:label>Email</flux:label>
                <flux:input :value="$email" type="email" readonly disabled />
                <p class="text-xs text-zinc-400 mt-1">Email cannot be changed here.</p>
            </flux:field>

            <div class="flex items-center gap-3 pt-1">
                <flux:button variant="primary" type="submit">Save Changes</flux:button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm overflow-hidden">
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
                <flux:button variant="primary" type="submit">Update Password</flux:button>
            </div>
        </form>
    </div>
</div>
