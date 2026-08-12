<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Support\Theme;
use Livewire\Component;

class ThemeSwitcher extends Component
{
    public bool $dark = false;

    public function mount(): void
    {
        $this->dark = Theme::isDark();
    }

    public function toggle(): void
    {
        $this->dark = ! $this->dark;

        Setting::set('theme_mode', $this->dark ? 'dark' : 'light');

        $this->dispatch('theme:toggled', mode: $this->dark ? 'dark' : 'light');
    }

    public function render()
    {
        return view('livewire.admin.theme-switcher');
    }
}
