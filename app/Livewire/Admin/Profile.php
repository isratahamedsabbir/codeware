<?php

namespace App\Livewire\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use PasswordValidationRules, ProfileValidationRules, WithFileUploads;

    public string $name = '';

    public string $email = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => $this->nameRules(),
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = ['name' => $validated['name']];

        if (! empty($this->photo)) {
            $path = $this->photo->storeAs(
                'profiles',
                Str::uuid()->toString() . '.' . $this->photo->getClientOriginalExtension(),
                'public',
            );

            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $data['photo'] = $path;
        }

        $user->update($data);

        $this->reset('photo');

        $this->dispatch('notify', message: 'Profile updated successfully');
        $this->dispatch('profile-updated', name: $user->fresh()->name);
    }

    public function removePhoto(): void
    {
        $user = auth()->user();

        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->update(['photo' => null]);

        $this->dispatch('notify', message: 'Photo removed');
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        auth()->user()->update(['password' => $validated['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('notify', message: 'Password updated successfully');
    }

    public function render()
    {
        return view('livewire.admin.profile', [
            'user' => auth()->user(),
        ])->layout('layouts.admin', ['title' => 'My Profile']);
    }
}
