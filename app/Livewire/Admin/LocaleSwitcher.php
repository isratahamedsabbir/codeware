<?php

namespace App\Livewire\Admin;

use App\Support\AdminActivity;
use App\Support\Locale;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    public string $current = '';

    public function mount(): void
    {
        $this->current = Locale::current();
    }

    /**
     * Switching is global — the locale lives in the `app_locale` setting, so this changes
     * the admin panel language for every user.
     */
    public function switchTo(string $code): void
    {
        if ($code === $this->current || ! Locale::isSupported($code)) {
            return;
        }

        Locale::set($code);

        $this->current = $code;

        AdminActivity::log('updated', 'Locale switched to '.$code);

        // Full reload so every already-rendered label picks up the new locale.
        $this->redirect(request()->header('Referer') ?: route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.locale-switcher', [
            'languages' => Locale::active(),
        ]);
    }
}
