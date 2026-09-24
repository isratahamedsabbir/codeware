<?php

namespace App\Livewire\Admin\ThemeSettings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Themes;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public bool $showInstallModal = false;

    /** @var TemporaryUploadedFile|null */
    public $themeZip = null;

    /**
     * The settings this screen owns: the active site design (site_theme) plus
     * the homepage copy & imagery the theme templates render. Boolean values
     * are stored as the string "0"/"1" (no cast on the Setting model), so they
     * are cast to real booleans here — see Settings\Index::loadSettings().
     */
    public array $settings = [];

    /** Most hero slides the homepage slider takes. */
    public const MAX_HERO_SLIDES = 6;

    /**
     * The homepage hero slider's images, in order (a URL per slide, '' for a
     * slide whose image hasn't been picked yet). Persisted as a JSON list in
     * `home_hero_slides`; the first one is mirrored into `home_hero_image` so
     * anything reading the single hero image keeps working.
     *
     * @var array<int, string>
     */
    public array $heroSlides = [];

    public function mount(): void
    {
        $keys = array_merge($this->keys(), $this->scopedThemeKeys());

        $rows = Setting::whereIn('key', $keys)->get()->keyBy('key');

        foreach ($keys as $key) {
            $row = $rows->get($key);
            $value = $row?->value ?? '';

            $this->settings[$key] = $row?->type === 'boolean' ? (bool) $value : (string) $value;
        }

        // Older installs only have the single hero image — it becomes slide 1.
        $slides = json_decode((string) Setting::get('home_hero_slides', ''), true);
        $this->heroSlides = is_array($slides) && $slides !== []
            ? array_values(array_map('strval', $slides))
            : [(string) ($this->settings['home_hero_image'] ?? '')];
    }

    public function addHeroSlide(): void
    {
        if (count($this->heroSlides) < self::MAX_HERO_SLIDES) {
            $this->heroSlides[] = '';
        }
    }

    public function removeHeroSlide(int $index): void
    {
        unset($this->heroSlides[$index]);

        $this->heroSlides = array_values($this->heroSlides) ?: [''];
    }

    public function save(): void
    {
        $slides = array_values(array_filter(array_map(fn ($url) => trim((string) $url), $this->heroSlides)));
        Setting::set('home_hero_slides', json_encode($slides));
        $this->settings['home_hero_image'] = $slides[0] ?? '';

        foreach ($this->savableKeys() as $key) {
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

    /**
     * The Install Theme header button (see @push('page-header-actions') in
     * index.blade.php, rendered outside this component's DOM root) has no
     * wire:id ancestor — it dispatches a window event the root <div> picks up,
     * same cross-DOM pattern as the Media Library's watermark button.
     */
    public function openInstallModal(): void
    {
        $this->resetErrorBag('themeZip');
        $this->showInstallModal = true;
    }

    public function closeInstallModal(): void
    {
        $this->reset('themeZip', 'showInstallModal');
    }

    /**
     * Installs a theme uploaded as zip of a single folder into
     * resources/views/frontend/themes/ (the folder name becomes the slug).
     * The zip's contents are validated before anything touches the themes
     * directory: path-traversal entries are rejected, total size is capped,
     * and an existing theme folder of the same slug is never overwritten.
     */
    public function installTheme(): void
    {
        $this->validate([
            'themeZip' => ['required', 'file', 'mimes:zip'],
        ]);

        $zip = new \ZipArchive;

        if ($zip->open($this->themeZip->getRealPath()) !== true) {
            $this->addError('themeZip', 'This file is not a valid zip theme package.');

            return;
        }

        $temp = tempnam(sys_get_temp_dir(), 'theme-install-');
        @unlink($temp);
        File::makeDirectory($temp, 0777, true, true);

        try {
            $this->extractThemeZip($zip, $temp);
            $zip->close();
        } catch (\Throwable $e) {
            $zip->close();
            File::deleteDirectory($temp);
            $this->addError('themeZip', $e->getMessage());

            return;
        }

        $root = $this->singleRootFolder($temp);

        if ($root === null) {
            File::deleteDirectory($temp);
            $this->addError('themeZip', 'The zip must contain exactly one theme folder at its root (plus unavoidable junk like __MACOSX is fine).');

            return;
        }

        $slug = $this->slugify($root);

        if ($slug === '') {
            File::deleteDirectory($temp);
            $this->addError('themeZip', 'The theme folder name could not be turned into a valid slug (letters, numbers, dashes and underscores only).');

            return;
        }

        $themesPath = Themes::path();
        File::ensureDirectoryExists($themesPath);

        if (is_dir($themesPath.'/'.$slug) || is_file($themesPath.'/'.$slug)) {
            File::deleteDirectory($temp);
            $this->addError('themeZip', "A theme named \"{$slug}\" already exists. Rename the folder inside the zip and try again.");

            return;
        }

        try {
            File::moveDirectory($temp.'/'.$root, $themesPath.'/'.$slug);
        } catch (\Throwable $e) {
            File::deleteDirectory($temp);
            $this->addError('themeZip', 'Could not move the theme into place: '.$e->getMessage());

            return;
        }

        File::deleteDirectory($temp);
        Themes::forget();

        AdminActivity::log('created', "Theme \"{$slug}\" installed");

        // A real reload so the new theme card appears in the picker.
        $this->showInstallModal = false;
        session()->flash('success', "Theme \"{$slug}\" installed.");
        $this->js('window.location.reload()');
    }

    /**
     * @throws \RuntimeException when an entry is unsafe or the package is too big
     */
    private function extractThemeZip(\ZipArchive $zip, string $temp): void
    {
        $tempRoot = realpath($temp);
        $totalSize = 0;
        $entryCount = $zip->numFiles;

        if ($entryCount > 2000) {
            throw new \RuntimeException('The zip contains too many files to be a theme (max 2000).');
        }

        for ($i = 0; $i < $entryCount; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));

            if ($name === '' || str_starts_with($name, '__MACOSX/')) {
                continue;
            }

            // Reject path traversal, absolute paths and drive-qualified paths.
            if (preg_match('#(^|/)\.\.(/|$)#', $name) || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:/.*$#', $name)) {
                throw new \RuntimeException('The zip contains unsafe file paths.');
            }

            $stat = $zip->statIndex($i);
            $totalSize += (int) $stat['size'];

            if ($totalSize > 52428800) {
                throw new \RuntimeException('The theme package is too large (max 50MB).');
            }

            $target = $temp.'/'.$name;

            if (str_ends_with($name, '/')) {
                File::makeDirectory($target, 0777, true, true);

                continue;
            }

            // mkdir before checking realpath: dirname() of a not-yet-created
            // nested path (and the file's own realpath, before it exists) both
            // resolve to false. The string checks above already rule out
            // traversal/absolute paths, so this is defense-in-depth.
            File::makeDirectory(dirname($target), 0777, true, true);

            $realDir = realpath(dirname($target));

            if ($realDir === false || ! str_starts_with($realDir, $tempRoot)) {
                throw new \RuntimeException('The zip contains unsafe file paths.');
            }

            file_put_contents($target, (string) $zip->getFromIndex($i));
        }
    }

    /**
     * The single top-level folder inside an extracted zip, or null when the zip
     * has extra top-level files/folders (junk entries like __MACOSX and dotfiles
     * are ignored).
     */
    private function singleRootFolder(string $temp): ?string
    {
        $entries = array_values(array_filter(
            scandir($temp) ?: [],
            fn (string $entry) => ! in_array($entry, ['.', '..', '__MACOSX'], true) && ! str_starts_with($entry, '.')
        ));

        $directories = array_values(array_filter($entries, fn (string $entry) => is_dir($temp.'/'.$entry)));
        $files = array_values(array_filter($entries, fn (string $entry) => ! is_dir($temp.'/'.$entry)));

        return count($directories) === 1 && count($files) === 0 ? $directories[0] : null;
    }

    private function slugify(string $name): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower($name)), '-');
    }

    public function render()
    {
        $themes = Themes::all();
        $selectedSlug = $this->settings['site_theme'] ?? Themes::active();

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
                    'manifest' => Themes::manifest($slug),
                    'hasSettings' => Themes::hasSettings($slug),
                ]];
            })
            ->all();

        return view('livewire.admin.theme-settings.index', [
            'themes' => $themes,
            'themeCards' => $themeCards,
            'activeTheme' => Themes::active(),
            'selectedSlug' => $selectedSlug,
            'selectedHasSettings' => Themes::hasSettings($selectedSlug),
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
            'popup_enabled',
            'popup_image',
            'popup_title',
            'popup_description',
            'popup_button_label',
            'popup_button_url',
        ];
    }

    /**
     * Every theme-scoped setting this screen persists — i.e. any stored
     * settings key using the "theme_{slug}_..." prefix that a theme's own
     * settings.blade.php reads/writes. Hydrated into the form on mount and
     * saved back on save(), so each theme's fields live under its own prefix
     * and can never collide with another theme's (or the core keys above).
     *
     * @return array<int, string>
     */
    private function scopedThemeKeys(): array
    {
        // Keys a theme's settings.blade.php declares (bound as settings.theme_*
        // or listed as 'theme_*' strings) count even before their first save,
        // so a brand-new field loads blank and persists like any other.
        $declared = [];
        foreach (glob(resource_path('views/frontend/themes/*/settings.blade.php')) ?: [] as $file) {
            preg_match_all('/(?:settings\.|[\'"])(theme_[a-z0-9_]+)/', (string) file_get_contents($file), $matches);
            array_push($declared, ...$matches[1]);
        }

        return array_values(array_unique([
            ...Setting::where('key', 'like', 'theme\\_%')->pluck('key')->all(),
            ...$declared,
        ]));
    }

    /**
     * What save() writes: the core keys plus every theme-scoped key currently
     * held in the form (the selected theme's settings.blade.php is the only
     * thing that populates theme_* fields, so those are the only scoped keys
     * the admin can ever change on this screen).
     *
     * @return array<int, string>
     */
    private function savableKeys(): array
    {
        $scoped = array_values(array_filter(
            array_keys($this->settings),
            fn (string $key) => str_starts_with($key, 'theme_')
        ));

        return array_values(array_unique([...$this->keys(), ...$scoped]));
    }
}
