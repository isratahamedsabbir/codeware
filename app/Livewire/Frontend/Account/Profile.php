<?php

namespace App\Livewire\Frontend\Account;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The storefront "My Profile" form — profile photo, name + email, plus an
 * optional password change (guarded by the current password). Plain
 * storefront styling, no Flux. The photo is stored like the admin user form's
 * (public disk, profiles/), replacing and deleting any previous file.
 */
class Profile extends Component
{
    use ProfileValidationRules;
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null A newly chosen photo, not yet saved. */
    public $photo = null;

    /** Set when the customer asks to remove their current photo. */
    public bool $removePhoto = false;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /** Validates a picked photo straight away so the preview never shows a bad file. */
    public function updatedPhoto(): void
    {
        $this->resetErrorBag('photo');
        $this->validateOnly('photo', ['photo' => $this->photoRules()]);
        $this->removePhoto = false;
    }

    public function removeCurrentPhoto(): void
    {
        $this->photo = null;
        $this->removePhoto = true;
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'photo' => $this->photoRules(),
            'name' => $this->nameRules(),
            'email' => $this->emailRules($user->id),
            'current_password' => ['nullable', 'required_with:password', 'string', 'current_password:web'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if (filled($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->photo = $this->persistPhoto($user->photo);

        $user->save();

        $this->reset('current_password', 'password', 'password_confirmation', 'photo', 'removePhoto');

        $this->dispatch('notify', message: __('Profile updated.'), type: 'success');
    }

    /**
     * @return array<int, string>
     */
    private function photoRules(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }

    /**
     * The photo path to store: a new upload replaces (and deletes) the old
     * file, a removal deletes it and returns null, otherwise nothing changes.
     */
    private function persistPhoto(?string $current): ?string
    {
        if (! $this->photo && ! $this->removePhoto) {
            return $current;
        }

        if ($current && Storage::disk('public')->exists($current)) {
            Storage::disk('public')->delete($current);
        }

        if (! $this->photo) {
            return null;
        }

        return $this->photo->storeAs(
            'profiles',
            Str::uuid()->toString().'.'.$this->photo->getClientOriginalExtension(),
            'public',
        );
    }

    public function resendVerificationNotification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('account.dashboard'));

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    public function render()
    {
        return view('livewire.frontend.account-profile');
    }
}
