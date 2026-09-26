<?php

namespace App\Livewire\Admin\ThemeSettings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\HeroSlides;
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
    public const MAX_HERO_SLIDES = HeroSlides::MAX;

    /**
     * Row cap for a repeater whose theme declaration omits `:max`. Mirrors the
     * `max` default on <x-admin-repeatable-fields> — the two must agree, or a
     * repeater with no declared cap would be refused by the server at a
     * different number of rows than the button offers.
     */
    private const DEFAULT_REPEATER_MAX = 24;

    /**
     * declaredRepeaterMax() re-reads every theme's settings.blade.php, and a
     * single save asks for the cap once per repeater, so the parse is memoised
     * for the lifetime of the request.
     *
     * @var array<string, int>|null
     */
    private ?array $repeaterMaxes = null;

    /**
     * The homepage hero slider, in order — each slide {image, title,
     * description, link} (see App\Support\HeroSlides). Persisted as a JSON
     * list in `home_hero_slides`; the first image is mirrored into
     * `home_hero_image` so anything reading the single hero image keeps working.
     *
     * @var array<int, array{image: string, title: string, description: string, link: string}>
     */
    public array $heroSlides = [];

    /**
     * Repeating theme fields, keyed by their "theme_{slug}_*" setting key.
     *
     * Some theme settings are lists rather than single values — the portfolio's
     * trust stats, service cards, education and certifications. Each is stored as
     * one JSON setting (see PortfolioProfile) rather than as a table, because
     * they are copy, not queryable content, and a theme that can be zipped out
     * cannot bring a migration with it.
     *
     * $repeaters is the form-side mirror of those settings: 'theme_portfolio_services'
     * => [ ['title' => ..., 'description' => ..., 'icon' => ...], ... ]. The keys
     * are discovered from the active theme's own settings.blade.php (see
     * declaredRepeaterKeys()), so this component stays theme-agnostic — a theme
     * that declares no repeaters simply gets an empty array.
     *
     * @var array<string, array<int, array<string, string>>>
     */
    public array $repeaters = [];

    public function mount(): void
    {
        $keys = array_merge($this->keys(), $this->scopedThemeKeys());

        $rows = Setting::whereIn('key', $keys)->get()->keyBy('key');

        foreach ($keys as $key) {
            $row = $rows->get($key);
            $value = $row?->value ?? '';

            $this->settings[$key] = $row?->type === 'boolean' ? (bool) $value : (string) $value;
        }

        $this->loadRepeaters();

        // Older installs (plain image URLs, or only the single hero image) load
        // as image-only slides.
        $this->heroSlides = HeroSlides::stored() ?: [HeroSlides::blank()];
    }

    /**
     * Hydrate every declared repeater from its stored JSON, normalised to a list
     * of flat string maps.
     *
     * A setting row is free text, so the value can be a JSON array, a JSON
     * object, or something a human typed into the database. None of those may
     * fatal the settings screen, and an unparseable value is treated as "no rows
     * yet" — which is the same state a brand-new field is in, so the form still
     * renders and the owner can retype it.
     *
     * This screen is deliberately more forgiving than the storefront, which drops
     * a JSON object outright (see App\Support\PortfolioProfile::rows()). Here a
     * value that decodes to an object is shown as one editable row, so a bad
     * write is something the owner can see and delete instead of something that
     * silently reappears as [] the next time they open the screen.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    protected function loadRepeaters(): void
    {
        $keys = $this->declaredRepeaterKeys();
        $rows = Setting::whereIn('key', $keys)->pluck('value', 'key');

        foreach ($keys as $key) {
            $decoded = json_decode((string) $rows->get($key, '[]'), true);

            $this->repeaters[$key] = is_array($decoded)
                ? collect($decoded)
                    ->filter(fn ($row) => is_array($row))
                    ->map(fn (array $row) => collect($row)
                        ->map(fn ($value) => is_scalar($value) ? (string) $value : '')
                        ->all())
                    ->values()
                    ->all()
                : [];
        }
    }

    /**
     * Add a blank row to a declared repeater.
     *
     * Refuses past the cap as well as hiding the button at it. The button is the
     * affordance for a person; this is the rule, and a Livewire method is
     * reachable directly by anything that can talk to the endpoint.
     *
     * @param  string  $key  the repeater's setting key
     * @param  array<int, string>  $fields  the field names a new row starts with
     */
    public function addRepeaterRow(string $key, array $fields = ['title']): void
    {
        if (! array_key_exists($key, $this->repeaters)) {
            return;
        }

        if (count($this->repeaters[$key]) >= $this->repeaterMax($key)) {
            return;
        }

        $this->repeaters[$key][] = array_fill_keys($fields, '');
    }

    public function removeRepeaterRow(string $key, int $index): void
    {
        if (! array_key_exists($key, $this->repeaters) || ! array_key_exists($index, $this->repeaters[$key])) {
            return;
        }

        unset($this->repeaters[$key][$index]);

        $this->repeaters[$key] = array_values($this->repeaters[$key]);
    }

    /**
     * Move a repeater row up or down. Reordering is the whole point of a list
     * setting — the storefront prints rows in stored order, so "add" alone would
     * leave the owner unable to put their best project first.
     */
    public function moveRepeaterRow(string $key, int $index, string $direction): void
    {
        if (! array_key_exists($key, $this->repeaters)) {
            return;
        }

        $rows = array_values($this->repeaters[$key]);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index < 0 || $index >= count($rows) || $target < 0 || $target >= count($rows)) {
            return;
        }

        [$rows[$index], $rows[$target]] = [$rows[$target], $rows[$index]];

        $this->repeaters[$key] = $rows;
    }

    public function addHeroSlide(): void
    {
        if (count($this->heroSlides) < self::MAX_HERO_SLIDES) {
            $this->heroSlides[] = HeroSlides::blank();
        }
    }

    public function removeHeroSlide(int $index): void
    {
        unset($this->heroSlides[$index]);

        $this->heroSlides = array_values($this->heroSlides) ?: [HeroSlides::blank()];
    }

    public function save(): void
    {
        $this->settings['chat_widget_color'] = strtolower(trim((string) ($this->settings['chat_widget_color'] ?? '')));

        $this->validate(
            ['settings.chat_widget_color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/']],
            ['settings.chat_widget_color.regex' => __('Enter a hex color like #1e7bc4.')],
        );

        // A slide without an image isn't shown, so it isn't kept either.
        $slides = array_values(array_filter(
            array_map(HeroSlides::normalize(...), $this->heroSlides),
            fn (array $slide) => $slide['image'] !== '',
        ));
        Setting::set('home_hero_slides', json_encode($slides));
        $this->settings['home_hero_image'] = $slides[0]['image'] ?? '';

        foreach ($this->savableKeys() as $key) {
            if (array_key_exists($key, $this->settings)) {
                Setting::set($key, $this->settings[$key]);
            }
        }

        $this->saveRepeaters();

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
     * Write every declared repeater back to its JSON setting.
     *
     * Rows are trimmed and re-indexed, and a row whose fields are all blank is
     * dropped: the repeater UI always leaves one empty row on screen for the
     * owner to fill in, and persisting that would put an empty card on the
     * public page. Trimming on the way in also means a value that used to render
     * as " Laravel" cannot reach the storefront after a round trip.
     *
     * Rows past the declared cap are dropped last. A cap is a layout promise
     * about how many rows the theme can lay out, so the stored list is truncated
     * to it even if a hand-crafted request arrived with more — the first rows
     * win, which is the same rows the reordering UI would have kept at the top.
     */
    protected function saveRepeaters(): void
    {
        foreach ($this->repeaters as $key => $rows) {
            $clean = collect($rows)
                ->map(fn ($row) => collect(is_array($row) ? $row : [])
                    ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : '')
                    ->all())
                ->reject(fn (array $row) => collect($row)->every(fn (string $value) => $value === ''))
                ->take($this->repeaterMax($key))
                ->values()
                ->all();

            // An emptied-out list is stored as [] rather than deleted, so the
            // storefront's "is this section empty" check and this form agree.
            Setting::set($key, json_encode($clean));
        }
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
            'chat_widget_color',
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

        // A repeater's key matches the pattern above too, because the theme names
        // it the same way. It is a list, not a text field, so it is excluded
        // here and lives only in $repeaters — otherwise save() would write its
        // JSON back through the scalar bag as a plain string.
        $repeaters = $this->declaredRepeaterKeys();

        return array_values(array_unique([
            ...array_diff(Setting::where('key', 'like', 'theme\\_%')->pluck('key')->all(), $repeaters),
            ...array_diff($declared, $repeaters),
        ]));
    }

    /**
     * Every theme-scoped setting that holds a *list* of rows, discovered the same
     * way scopedThemeKeys() discovers scalar ones: a theme declares one by
     * rendering <x-admin-repeatable-fields setting-key="theme_{slug}_*">, and this
     * screen grows the matching add/remove/reorder UI for it.
     *
     * The marker is a distinct attribute rather than a plain `key="theme_..."`
     * because that is exactly how a *scalar* theme setting is declared, and the
     * two must not be confused: loading a repeater's key into the scalar
     * $settings bag would have save() write its raw JSON back as a text value
     * and wipe the rows. That is why scopedThemeKeys() subtracts these.
     *
     * @return array<int, string>
     */
    private function declaredRepeaterKeys(): array
    {
        $declared = [];
        foreach (glob(resource_path('views/frontend/themes/*/settings.blade.php')) ?: [] as $file) {
            preg_match_all('/setting-key=[\'"](theme_[a-z0-9_]+)[\'"]/', (string) file_get_contents($file), $matches);
            array_push($declared, ...$matches[1]);
        }

        return array_values(array_unique($declared));
    }

    /**
     * The row cap each declared repeater ships with, keyed by setting key.
     *
     * A cap is a *layout* decision — the portfolio trust strip is a four-column
     * grid, so a fifth stat has nowhere to go — and the theme states it once, as
     * :max on the component. Reading it back from the same declaration keeps the
     * button that offers the row and the server that refuses it in agreement,
     * instead of leaving the cap to live only in the blade where a crafted
     * request walks straight past it.
     *
     * Read per-file rather than by scanning the whole file for numbers, so a
     * `:max` belonging to some other component on the page cannot be mistaken
     * for this one's.
     *
     * @return array<string, int>
     */
    private function declaredRepeaterMax(): array
    {
        if ($this->repeaterMaxes !== null) {
            return $this->repeaterMaxes;
        }

        $maxes = [];

        foreach (glob(resource_path('views/frontend/themes/*/settings.blade.php')) ?: [] as $file) {
            $source = (string) file_get_contents($file);

            // Each declaration is a single <x-admin-repeatable-fields ... /> tag;
            // capturing the tag body and reading the two attributes out of it is
            // what keeps one repeater's :max from bleeding into the next.
            preg_match_all('/<x-admin-repeatable-fields\b(.*?)\/>/s', $source, $tags);

            foreach ($tags[1] as $tag) {
                if (! preg_match('/setting-key=[\'"](theme_[a-z0-9_]+)[\'"]/', $tag, $key)) {
                    continue;
                }

                if (preg_match('/:max=[\'"](\d+)[\'"]/', $tag, $max)) {
                    $maxes[$key[1]] = max(1, (int) $max[1]);
                }
            }
        }

        return $this->repeaterMaxes = $maxes;
    }

    /**
     * The cap that applies to one repeater, falling back to the component's own
     * default when a theme omits :max.
     */
    private function repeaterMax(string $key): int
    {
        return $this->declaredRepeaterMax()[$key] ?? self::DEFAULT_REPEATER_MAX;
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
