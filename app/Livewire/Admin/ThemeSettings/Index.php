<?php

namespace App\Livewire\Admin\ThemeSettings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Themes;
use Livewire\Component;

class Index extends Component
{
    /**
     * The settings this screen owns: the active site design (site_theme) plus
     * the homepage copy & imagery the theme templates render. Boolean values
     * are stored as the string "0"/"1" (no cast on the Setting model), so they
     * are cast to real booleans here — see Settings\Index::loadSettings().
     */
    public array $settings = [];

    public function mount(): void
    {
        $rows = Setting::whereIn('key', $this->keys())->get()->keyBy('key');

        foreach ($this->keys() as $key) {
            $row = $rows->get($key);
            $value = $row?->value ?? '';

            $this->settings[$key] = $row?->type === 'boolean' ? (bool) $value : (string) $value;
        }
    }

    public function save(): void
    {
        foreach ($this->keys() as $key) {
            if (array_key_exists($key, $this->settings)) {
                Setting::set($key, $this->settings[$key]);
            }
        }

        AdminActivity::log('updated', 'Theme settings updated');

        // A real browser reload, same as Settings\Index::save(), so anything
        // rendered from Setting::get() (e.g. the active theme) re-reads fresh.
        session()->flash('success', 'Theme settings saved.');
        $this->js('window.location.reload()');
    }

    public function resetSettings(): void
    {
        $this->mount();
    }

    public function render()
    {
        $themes = Themes::all();

        $themeCards = collect($themes)
            ->mapWithKeys(function (string $label, string $slug): array {
                $base = Themes::path().'/'.$slug;
                $files = is_dir($base)
                    ? array_merge(glob($base.'/*.blade.php') ?: [], glob($base.'/*/*.blade.php') ?: [])
                    : [];

                return [$slug => [
                    'label' => $label,
                    'templates' => count($files),
                    'shop' => is_file($base.'/shop.blade.php'),
                ]];
            })
            ->all();

        return view('livewire.admin.theme-settings.index', [
            'themes' => $themes,
            'themeCards' => $themeCards,
            'activeTheme' => Themes::active(),
        ])->layout('layouts.admin', ['title' => 'Theme Settings']);
    }

    /**
     * The exact keys this screen controls. Kept in sync with the field widgets
     * in the blade view; the homepage image keys are read by the storefront
     * home template (see resources/views/frontend/themes/ecommerce/home.blade.php).
     *
     * @return array<int, string>
     */
    private function keys(): array
    {
        return [
            'site_theme',
            'chat_widget_enabled',
            'site_tagline',
            'home_hero_image',
            'home_promo_banner_1',
            'home_promo_banner_2',
        ];
    }
}
