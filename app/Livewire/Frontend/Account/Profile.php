<?php

namespace App\Livewire\Frontend\Account;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * The storefront "My Profile" form — name + email, plus an optional password
 * change (guarded by the current password). Plain storefront styling, no Flux.
 */
class Profile extends Component
{
    use ProfileValidationRules;

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

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
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

        $user->save();

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('notify', message: __('Profile updated.'), type: 'success');
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
