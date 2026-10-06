<?php

namespace App\Livewire\Admin\ThemeSettings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\HeroSlides;
use App\Support\SafeUpload;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * The Create Theme form. Kept out of $settings on purpose: those are the
     * site-wide values this screen saves to the settings table, and a half-typed
     * theme name is none of their business — every one of them is written into
     * the new theme's own theme.json by Themes::create() instead.
     */
    public string $newName = '';

    public string $newSlug = '';

    public string $newVersion = '1.0.0';

    public string $newDescription = '';

    public string $newAuthor = '';

    public bool $showCreateModal = false;

    /** The theme the Delete modal is open for, and why it may be undeletable. */
    public ?string $themeToDelete = null;

    public ?string $deleteBlockedBy = null;

    public bool $showDeleteModal = false;

    /**
     * Whether the owner has typed the slug themselves. One-way: once the field has
     * been edited by hand the name stops rewriting it, because a slug that keeps
     * changing under someone who is fixing a typo in it is worse than one they
     * have to retype. The Create button clears it along with the rest of the form.
     *
     * Public because it has to survive the round trip to the browser: every
     * keystroke is its own request and the component is rebuilt from the payload,
     * so a flag that stayed private would be reset to false on each one and the
     * name would keep overwriting a slug that had been edited a second earlier.
     */
    public bool $slugEdited = false;

    /**
     * The site-wide settings this screen owns: the active site design
     * (site_theme), the chat widget, the announcement popup and the homepage
     * copy & imagery the theme templates render. Boolean values are stored as
     * the string "0"/"1" (no cast on the Setting model), so they are cast to
     * real booleans here — see Settings\Index::loadSettings().
     *
     * A theme's *own* settings are not in here for the database's sake but only
     * because the form binds them: every theme_{slug}_* key in this bag is
     * routed to that theme's theme.json on save (see saveThemeValues()). They
     * are not settings-table rows, and nothing outside this screen reads them
     * from one.
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
     * settingsDeclarations() reads every theme's settings.blade.php, and each
     * discovery method below greps the same few files, so the raw sources are
     * memoised for the lifetime of the request.
     *
     * @var array<int, string>|null
     */
    private ?array $declarations = null;

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
     * Repeating theme fields, keyed by their "theme_{slug}_*" key.
     *
     * Some theme settings are lists rather than single values — the portfolio's
     * trust stats, project cards, experience entries, education and
     * certifications. Each is stored as one entry in that theme's theme.json
     * (see App\Support\ThemeSettings) rather than as a table, because they are
     * copy, not queryable content, and a theme that can be zipped out cannot
     * bring a migration with it.
     *
     * $repeaters is the form-side mirror of those: 'theme_portfolio_projects'
     * => [ ['title' => ..., 'description' => ..., 'icon' => ...], ... ]. The keys
     * are discovered from the active theme's own settings.blade.php (see
     * declaredRepeaterKeys()), so this component stays theme-agnostic — a theme
     * that declares no repeaters simply gets an empty array.
     *
     * @var array<string, array<int, array<string, string>>>
     */
    public array $repeaters = [];

    /**
     * Pending direct uploads, keyed by their "theme_{slug}_*" key — the
     * fields a theme declares with <x-admin-theme-upload upload-key="..." />
     * (see declaredUploads()). The file is only stored on save(), where it
     * replaces the value in the theme's theme.json and the previously
     * uploaded file is deleted.
     *
     * @var array<string, TemporaryUploadedFile|null>
     */
    public array $uploads = [];

    /** Where direct theme uploads live on the public disk. */
    private const UPLOAD_DIR = 'theme-uploads';

    public function mount(): void
    {
        $rows = Setting::whereIn('key', $this->keys())->get()->keyBy('key');

        foreach ($this->keys() as $key) {
            $row = $rows->get($key);
            $value = $row?->value ?? '';

            $this->settings[$key] = $row?->type === 'boolean' ? (bool) $value : (string) $value;
        }

        $this->loadThemeValues();

        // Older installs (plain image URLs, or only the single hero image) load
        // as image-only slides.
        $this->heroSlides = HeroSlides::stored() ?: [HeroSlides::blank()];
    }

    /**
     * Hydrate every declared theme field from the theme's own theme.json.
     *
     * Every installed theme is read, not just the selected one. The theme picker
     * is a live radio bound to settings.site_theme, so switching themes swaps
     * which settings.blade.php is included without a page load, and the values
     * that partial binds to have to already be in the bags by then. Each theme
     * reads only the keys its own file declares, so the themes cannot collide
     * even though they share one form.
     */
    protected function loadThemeValues(): void
    {
        $stored = $this->storedThemeValues();

        foreach ($this->declaredScalarKeys() as $key) {
            $value = $stored[$key] ?? null;

            $this->settings[$key] = is_scalar($value) ? (string) $value : '';
        }

        foreach ($this->declaredRepeaterKeys() as $key) {
            $this->repeaters[$key] = $this->normaliseRows($stored[$key] ?? null);
        }
    }

    /**
     * Every declared theme value, flattened out of each installed theme's
     * theme.json into one key => value map.
     *
     * Flat because the keys are already namespaced by the `theme_{slug}_`
     * prefix every theme declares its fields under, and the form bag is a single
     * flat array. Which theme each key came from is recovered on save by
     * themeSlugFor().
     *
     * @return array<string, mixed>
     */
    private function storedThemeValues(): array
    {
        $values = [];

        foreach (array_keys(Themes::all()) as $slug) {
            foreach (ThemeSettings::all($slug) as $key => $value) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * A stored list value as the flat list of string maps the repeater UI edits,
     * or an empty list when there is nothing usable there.
     *
     * A theme.json is free to be hand-edited, so the value can be a JSON
     * array, a JSON list that has been pasted in as a *string*, or something
     * that is not JSON at all. None of those may fatal the settings screen, and
     * an unparseable value is treated as "no rows yet" — which is the same state
     * a brand-new field is in, so the form still renders and the owner can retype
     * it.
     *
     * This screen is deliberately more forgiving than the storefront, which drops
     * a JSON object outright (see App\Support\PortfolioProfile::rows()). Here a
     * value that decodes to an object is shown as one editable row, so a bad
     * write is something the owner can see and delete instead of something that
     * silently reappears as [] the next time they open the screen.
     *
     * @return array<int, array<string, string>>
     */
    private function normaliseRows(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode(trim($raw), true);
        }

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => collect($row)
                ->map(fn ($value) => is_scalar($value) ? (string) $value : '')
                ->all())
            ->values()
            ->all();
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

    /**
     * Validate a direct upload as soon as it lands, so a wrong file type is
     * reported next to the field rather than only on save.
     */
    public function updatedUploads(mixed $value, string $key): void
    {
        if (! array_key_exists($key, $this->declaredUploads())) {
            unset($this->uploads[$key]);

            return;
        }

        $this->validateOnly('uploads.'.$key, $this->uploadRules());
    }

    /**
     * Clear a direct-upload field. The stored file itself is deleted on save,
     * so closing the page without saving leaves the live site untouched.
     */
    public function clearThemeUpload(string $key): void
    {
        if (! array_key_exists($key, $this->declaredUploads())) {
            return;
        }

        unset($this->uploads[$key]);
        $this->settings[$key] = '';
        $this->resetErrorBag('uploads.'.$key);
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

        $this->validate($this->uploadRules());

        // A slide without an image isn't shown, so it isn't kept either.
        $slides = array_values(array_filter(
            array_map(HeroSlides::normalize(...), $this->heroSlides),
            fn (array $slide) => $slide['image'] !== '',
        ));
        Setting::set('home_hero_slides', json_encode($slides));
        $this->settings['home_hero_image'] = $slides[0]['image'] ?? '';

        $this->saveUploads();

        // The theme files, before the site-wide rows: if one of them cannot be
        // written the owner needs to hear about that even though the rest of the
        // screen saved, so the failure is carried into the flash below.
        $skipped = $this->saveThemeValues();

        foreach ($this->keys() as $key) {
            if (array_key_exists($key, $this->settings)) {
                Setting::set($key, $this->settings[$key]);
            }
        }

        AdminActivity::log('updated', 'Theme settings updated');

        // A real browser reload, same as Settings\Index::save(), so anything
        // rendered from Setting::get() (e.g. the active theme) re-reads fresh.
        session()->flash('success', $skipped === []
            ? 'Theme settings saved.'
            : 'Theme settings saved, but the '.implode(', ', $skipped).' theme\'s own fields were not written: its theme.json is missing, or the web server user cannot write to themes/ (check the folder and file permissions).');

        $this->js('window.location.reload()');
    }

    /**
     * Write every declared theme field back into the theme's own theme.json.
     *
     * One file per theme, and every key routed by the `theme_{slug}_` prefix the
     * theme's settings.blade.php already declares it under (see
     * themeSlugFor()), so a save can never write one theme's values into another
     * theme's file.
     *
     * Only themes that already have a file are written. A theme whose
     * theme.json is missing has nowhere to save to, and quietly creating one
     * here would take that decision away from the owner — the Theme Settings card
     * says so and offers the button instead. Those themes come back as the list
     * of slugs whose fields were not written, which the caller turns into a
     * warning next to the success message.
     *
     * @return array<int, string>
     */
    protected function saveThemeValues(): array
    {
        $scalars = $this->declaredScalarKeys();
        $repeaters = $this->declaredRepeaterKeys();
        $skipped = [];

        foreach (array_keys(Themes::all()) as $slug) {
            $values = [];

            foreach ($scalars as $key) {
                if ($this->themeSlugFor($key) === $slug && array_key_exists($key, $this->settings)) {
                    $values[$key] = (string) $this->settings[$key];
                }
            }

            foreach ($repeaters as $key) {
                if ($this->themeSlugFor($key) === $slug && array_key_exists($key, $this->repeaters)) {
                    $values[$key] = $this->cleanRepeaterRows($key, $this->repeaters[$key]);
                }
            }

            if ($values === []) {
                continue;
            }

            if (! ThemeSettings::exists($slug) || ! ThemeSettings::merge($slug, $values)) {
                $skipped[] = $slug;
            }
        }

        return $skipped;
    }

    /**
     * One repeater's rows, ready to be stored.
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
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, string>>
     */
    private function cleanRepeaterRows(string $key, array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => collect(is_array($row) ? $row : [])
                ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : '')
                ->all())
            ->reject(fn (array $row) => collect($row)->every(fn (string $value) => $value === ''))
            ->take($this->repeaterMax($key))
            ->values()
            ->all();
    }

    public function resetSettings(): void
    {
        $this->mount();
    }

    /**
     * Store every pending direct upload and delete the file it replaces.
     *
     * The old file is only deleted when it is one this screen uploaded (it
     * lives under UPLOAD_DIR) — a value that points into the Media Library or
     * at an external URL is left alone, since other content may use it.
     */
    protected function saveUploads(): void
    {
        $disk = Storage::disk('public');

        foreach ($this->declaredUploads() as $key => $type) {
            $slug = $this->themeSlugFor($key);
            $old = ThemeSettings::text($slug, $key);
            $file = $this->uploads[$key] ?? null;

            if ($file instanceof TemporaryUploadedFile) {
                $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $type;
                $name = Str::limit($base, 60, '').'-'.Str::lower(Str::random(6)).'.'.SafeUpload::extension($file);
                $path = $file->storeAs(self::UPLOAD_DIR.'/'.str_replace('_', '-', $key), $name, 'public');

                $this->settings[$key] = $disk->url($path);
            }

            $new = (string) ($this->settings[$key] ?? '');
            $oldPath = $this->managedUploadPath($old);

            if ($oldPath !== null && $new !== $old) {
                $disk->delete($oldPath);
            }
        }

        $this->uploads = [];
    }

    /**
     * The public-disk path of a URL this screen uploaded, or null for anything
     * else (Media Library files, external links, blank).
     */
    private function managedUploadPath(string $url): ?string
    {
        $marker = '/storage/'.self::UPLOAD_DIR.'/';
        $position = strpos($url, $marker);

        if ($position === false) {
            return null;
        }

        $path = substr($url, $position + strlen('/storage/'));

        return str_contains($path, '..') ? null : $path;
    }

    /**
     * Validation rules for every declared direct upload, by its type.
     *
     * @return array<string, array<int, string>>
     */
    private function uploadRules(): array
    {
        return collect($this->declaredUploads())
            ->mapWithKeys(fn (string $type, string $key) => ['uploads.'.$key => $type === 'pdf'
                ? ['nullable', 'file', 'mimes:pdf', 'max:10240']
                : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']])
            ->all();
    }

    /**
     * Direct-upload fields the theme settings files declare, as key => type
     * ("image" or "pdf"). Read from <x-admin-theme-upload upload-key="..."
     * type="..." /> the same way repeaters are discovered.
     *
     * @return array<string, string>
     */
    private function declaredUploads(): array
    {
        $uploads = [];

        foreach ($this->settingsDeclarations() as $source) {
            preg_match_all('/<x-admin-theme-upload\b(.*?)\/>/s', $source, $tags);

            foreach ($tags[1] as $tag) {
                if (! preg_match('/upload-key=[\'"](theme_[a-z0-9_]+)[\'"]/', $tag, $key)) {
                    continue;
                }

                $uploads[$key[1]] = preg_match('/\btype=[\'"]pdf[\'"]/', $tag) ? 'pdf' : 'image';
            }
        }

        return $uploads;
    }

    /**
     * The blank theme.json a theme should start life with: every field the theme
     * declares in its own settings.blade.php, each at its empty default, so the
     * new file is also a readable statement of what the theme can be configured
     * with rather than a bare `{}`.
     *
     * Uploads are in here alongside scalars and repeaters. They are strings in
     * the file like any other field, and a declared upload field missing from the
     * file is the same present-and-blank confusion as a declared text field
     * missing from it — the form renders, the value has nowhere to live, and the
     * owner concludes the upload was thrown away.
     *
     * Keys are attributed to a theme by prefix, longest slug first, so a theme
     * whose slug is a prefix of another's ("shop" and "shop_plus") cannot claim
     * its neighbour's fields.
     *
     * @return array<string, mixed>
     */
    private function themeFileSkeleton(string $slug): array
    {
        $skeleton = [];

        foreach ($this->declaredScalarKeys() as $key) {
            if ($this->themeSlugFor($key) === $slug) {
                $skeleton[$key] = '';
            }
        }

        foreach ($this->declaredRepeaterKeys() as $key) {
            if ($this->themeSlugFor($key) === $slug) {
                $skeleton[$key] = [];
            }
        }

        foreach (array_keys($this->declaredUploads()) as $key) {
            if ($this->themeSlugFor($key) === $slug) {
                $skeleton[$key] = '';
            }
        }

        return $skeleton;
    }

    /**
     * (Re)create the selected theme's theme.json, seeded with every field it
     * declares. The button on the right of the Theme Settings card.
     *
     * The file is part of the theme folder, so it goes missing in entirely
     * ordinary ways: a deploy that ships templates but not content, a theme
     * re-uploaded from an older zip, a `git checkout` of a branch that predates
     * it, an owner tidying up. Without this the form above still renders — it is
     * declared by the theme's settings.blade.php, not by the file — but every
     * field is blank and Save quietly has nowhere to put them, which reads as
     * "the admin panel lost my settings" rather than as a missing file.
     *
     * Refuses to touch a file that is already there. This is the recovery path,
     * not a reset: a create that overwrote would be one click away from wiping a
     * finished theme's content, and it says so rather than appearing to succeed.
     */
    public function createSettingsFile(): void
    {
        $slug = $this->selectedSlug();

        if ($slug === '' || ! Themes::hasSettings($slug)) {
            return;
        }

        if (ThemeSettings::exists($slug)) {
            session()->flash('success', "The \"{$slug}\" theme already has a theme.json file — nothing to create.");

            return;
        }

        if (! ThemeSettings::create($slug, $this->themeFileSkeleton($slug))) {
            $this->addError('settingsFile', 'Could not write '.Themes::path()."/{$slug}/".ThemeSettings::FILE.'. Check that the theme folder is writable.');

            return;
        }

        $this->loadThemeValues();

        AdminActivity::log('created', "theme.json created for the \"{$slug}\" theme");

        session()->flash('success', 'Created '.ThemeSettings::FILE." for the \"{$slug}\" theme. Add your values below and save.");
        $this->js('window.location.reload()');
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
     * The New Theme header button, same cross-DOM arrangement as openInstallModal()
     * — see the note there.
     */
    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->reset('newName', 'newSlug', 'newVersion', 'newDescription', 'newAuthor', 'showCreateModal');
        $this->newVersion = '1.0.0';
        $this->slugEdited = false;
        $this->resetErrorBag();
    }

    /**
     * Keep the slug in step with the name while it is still derived from it.
     */
    public function updatedNewName(): void
    {
        if (! $this->slugEdited) {
            $this->newSlug = $this->slugify($this->newName);
        }
    }

    /**
     * Writes the new theme's folder from the basics given here: every page
     * template, its header/footer/404 partials, its own stylesheet, its settings
     * screen, a commented routes file and its theme.json, all in one folder.
     *
     * Nothing is activated. The theme appears on the picker above as a card to
     * choose from, and choosing it is a separate press of Save on this screen —
     * a theme that took the site live the moment it was created would replace a
     * finished design with placeholders.
     */
    public function createTheme(): void
    {
        $this->validate([
            'newName' => 'required|string|max:191',
            'newSlug' => ['required', 'string', 'max:191', function ($attribute, $value, $fail) {
                if (! ThemeSettings::isValidSlug($value)) {
                    $fail(__('Use letters, numbers, dashes and underscores only.'));

                    return;
                }

                if (file_exists(Themes::path().'/'.$value)) {
                    $fail(__('A theme with this slug already exists.'));
                }
            }],
            'newVersion' => 'required|string|max:32',
            'newDescription' => 'nullable|string|max:255',
            'newAuthor' => 'nullable|string|max:191',
        ]);

        try {
            $slug = Themes::create([
                'name' => $this->newName,
                'slug' => $this->newSlug,
                'version' => $this->newVersion,
                'description' => $this->newDescription,
                'author' => $this->newAuthor,
            ]);
        } catch (\RuntimeException $e) {
            $this->addError('newSlug', $e->getMessage());

            return;
        }

        AdminActivity::log('created', "Theme \"{$slug}\" created");

        $this->closeCreateModal();

        // The picker and this screen's own declarations memo both read the themes
        // directory, and a newly written folder has to be in both before the form
        // below renders — see Themes::create(), which clears the cached scan, and
        // the reload that puts the new card in the grid.
        $this->declarations = null;
        $this->repeaterMaxes = null;

        session()->flash('success', "Theme \"{$slug}\" created with a folder of its own under themes/{$slug}. Fill in its templates, then pick it above to go live.");

        $this->js('window.location.reload()');
    }

    /**
     * Installs a theme uploaded as zip of a single folder into
     * themes/ (the folder name becomes the slug).
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

        // The name the theme will go by once it is installed, which is its own
        // manifest name if it ships one and the slug-derived fallback otherwise —
        // checked here, before anything is moved into place, because a name
        // collision is a reason to refuse the package and not to leave a folder
        // behind for the owner to clean up.
        $name = $this->packagedThemeName($temp.'/'.$root, $slug);

        if (! Themes::isNameUnique($name)) {
            $clash = Themes::themeWithName($name);

            File::deleteDirectory($temp);
            $this->addError('themeZip', is_string($clash)
                ? 'This package\'s theme is called "'.Themes::manifest($clash)['name'].'", which an installed theme already uses. Theme names have to be unique — change the "name" in the zip\'s '.ThemeSettings::FILE.' and upload it again.'
                : 'This package\'s theme has no usable name. Give it one in the '.ThemeSettings::FILE.' inside the zip.');

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
        Themes::registerViews($slug);

        // The theme is on disk now, and so is its settings.blade.php if it ships
        // one — so the declaration scan has to read the files again. It is
        // memoised for the request, and an earlier call on this screen would
        // otherwise leave a new theme's own fields invisible to the skeleton
        // below.
        $this->declarations = null;
        $this->repeaterMaxes = null;

        $seeded = $this->seedInstalledThemeFile($slug);

        // Covers the one path seedInstalledThemeFile() leaves: a theme that
        // shipped its own theme.json. After a create() the number is already there
        // and this is a no-op.
        $this->assignSerialNumber($slug);

        $sn = Themes::manifest($slug)['sn'];

        AdminActivity::log('created', 'Theme "'.$slug.'" installed'.($sn > 0 ? ' (SN '.$sn.')' : ''));

        // A real reload so the new theme card appears in the picker.
        $this->showInstallModal = false;
        session()->flash(match ($seeded) {
            'created' => 'success',
            // The theme is installed either way — this is an optional file, and
            // failing the install over it would be the wrong trade — but saying
            // "installed" and nothing else would leave the owner to find out why
            // the fields render blank. This says what to do about it instead.
            'failed' => 'error',
            default => 'success',
        }, match ($seeded) {
            'created' => 'Theme "'.$slug.'" installed as '.$sn.'. Created its '.ThemeSettings::FILE.' with every field it declares — fill them in below.',
            'manifest' => 'Theme "'.$slug.'" installed as '.$sn.'. It declares no settings of its own, so its '.ThemeSettings::FILE.' holds nothing but its name and serial number.',
            'failed' => 'Theme "'.$slug.'" installed, but its '.ThemeSettings::FILE.' could not be written (check that the theme folder is writable). Select it and use "Create '.ThemeSettings::FILE.'".',
            default => 'Theme "'.$slug.'" installed'.($sn > 0 ? ' as '.$sn : '').'.',
        });
        $this->js('window.location.reload()');
    }

    /**
     * Hands the theme back as a .zip holding its own folder — a backup before a
     * round of hand-editing, or a file to share with someone else.
     *
     * The archive is the same shape installTheme() accepts, so a theme that is
     * downloaded and uploaded again comes back whole: templates, controllers,
     * database files and its public/ folder all travel in it, and its
     * theme.json goes with them, which is what keeps its settings with it.
     */
    public function downloadTheme(string $slug)
    {
        try {
            $zipPath = Themes::toZip($slug);
        } catch (\RuntimeException $e) {
            $this->addError('themes', $e->getMessage());

            return null;
        }

        AdminActivity::log('updated', "Theme \"{$slug}\" downloaded as a zip");

        return response()->download($zipPath, "{$slug}.zip")->deleteFileAfterSend(true);
    }

    /**
     * Opens the confirmation for one theme, carrying the reason it cannot be
     * deleted along so the modal can explain a greyed-out Delete rather than the
     * owner having to work out why nothing happened.
     */
    public function confirmDelete(string $slug): void
    {
        if (! array_key_exists($slug, Themes::all())) {
            return;
        }

        $this->themeToDelete = $slug;
        $this->deleteBlockedBy = Themes::undeletableBecause($slug);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->reset('themeToDelete', 'deleteBlockedBy', 'showDeleteModal');
        $this->resetErrorBag();
    }

    /**
     * Removes the theme folder confirmed above. Everything in it goes: templates,
     * controllers, migrations, seeders, its public/ files, and its theme.json —
     * which is where its settings are stored, so those are gone with the folder
     * and are not recoverable from here.
     *
     * The delete is refused for the live theme, for the last installed theme and
     * for one whose manifest says it is a default, both here and in
     * Themes::delete(), so a stale card cannot delete the theme the site is
     * being served from.
     */
    public function deleteTheme(): void
    {
        $slug = (string) $this->themeToDelete;

        // Checked before the guard reasons, because themeToDelete is a public
        // property and so can be set on this component without going through
        // confirmDelete() — including to a string like "../storage", which is a
        // real directory relative to themes/. Themes::delete() refuses those too;
        // this is what turns that refusal into a message instead of a shrug.
        if ($slug === '' || ! Themes::isInstalled($slug)) {
            $this->addError('themeToDelete', 'No such theme is installed.');

            return;
        }

        if (($blocked = Themes::undeletableBecause($slug)) !== null) {
            $this->addError('themeToDelete', $blocked);
            $this->deleteBlockedBy = $blocked;

            return;
        }

        $name = Themes::manifest($slug)['name'];

        if (! Themes::delete($slug)) {
            $this->addError('themeToDelete', "Could not delete \"{$name}\". Check that the themes directory is writable.");

            return;
        }

        AdminActivity::log('deleted', "Theme \"{$slug}\" deleted");

        $this->declarations = null;
        $this->repeaterMaxes = null;

        $this->closeDeleteModal();

        session()->flash('success', "Theme \"{$name}\" and all of its files have been deleted.");

        // A real reload: the picker above, this screen's own field declarations and
        // the theme.json of whatever is selected all have to stop reading a theme
        // that is no longer on disk.
        $this->js('window.location.reload()');
    }

    /**
     * Give a freshly installed theme the theme.json it should have shipped with,
     * seeded with every field its settings.blade.php declares.
     *
     * Installing a theme used to leave the owner one more thing to do: pick the
     * new theme on the screen, notice the amber panel saying it has no
     * theme.json, and click Create. That is a five-step install ending in a
     * manual recovery step, and it is the step a new theme author is least
     * likely to know about — the theme looked installed, and the fields that do
     * render save to nowhere until you go looking for why. Doing it here means
     * "install a theme, pick it, edit it" is the whole story.
     *
     * A theme that ships its own theme.json is left completely alone, manifest
     * and content both — the file is the theme author's, and this only fills a
     * gap. A theme that fails to get one is still installed; the amber panel and
     * its Create button are already the right answer for that, and swallowing
     * the install to report a permissions problem on an optional file would be
     * the wrong trade.
     *
     * A theme declaring no fields does get a file, holding only a manifest. That
     * used to be skipped, because there were no fields to put in it — but the
     * file is also where a theme's name and serial number live, so skipping it
     * left a theme that had been installed with no identity at all, and one that
     * only got an SN once somebody went and saved its settings form by hand.
     *
     * @return 'created'|'manifest'|'skipped'|'failed'
     */
    private function seedInstalledThemeFile(string $slug): string
    {
        if (ThemeSettings::exists($slug)) {
            return 'skipped';
        }

        // A theme that ships no settings.blade.php declares no fields, so the
        // file holds a manifest and nothing else. That is not the same as a
        // theme with no file at all — see above.
        $values = Themes::hasSettings($slug) ? $this->themeFileSkeleton($slug) : [];

        if (! ThemeSettings::create($slug, $values)) {
            return 'failed';
        }

        return $values === [] ? 'manifest' : 'created';
    }

    /**
     * Give a theme the serial number it does not have, right after it is
     * installed.
     *
     * Serial numbers are the installer's job rather than the theme author's, and
     * this is the only moment the app can be sure a number is free — the check in
     * Themes::nextSn() is against what is installed now, so handing out a number
     * any earlier would race a second install.
     *
     * Only fills in a theme that has none, and only a file this app can write
     * into. A theme that shipped a theme.json already carrying an SN keeps it,
     * which is what makes a theme zip that has been round this loop before come
     * back with the number it left with — the identity the rest of the site has
     * been using all along. A file that is missing was given one by
     * seedInstalledThemeFile() and already has its number, so the only case left
     * here is a theme author's own file, and one this cannot parse is left for
     * the owner to fix rather than overwritten on their behalf.
     *
     * Best-effort: a file that cannot be written is reported by the Theme
     * Settings card rather than failing an install over an optional field.
     */
    private function assignSerialNumber(string $slug): void
    {
        if (! ThemeSettings::exists($slug)
            || ThemeSettings::all($slug) === []
            || Themes::manifest($slug)['sn'] > 0) {
            return;
        }

        ThemeSettings::merge($slug, ['sn' => Themes::nextSn()]);
    }

    /**
     * The name a theme inside an unpacked zip will go by once installed.
     *
     * Its own manifest name if the package ships a usable one, and the same
     * slug-derived fallback Themes::manifest() falls back to otherwise — read from
     * the extracted folder rather than from the installed themes directory,
     * because at this point the package has not been installed and
     * Themes::manifest() would answer about some other theme entirely.
     *
     * The fallback matters as much as the manifest. A package with no theme.json
     * is installed under a name derived from its folder, and two slugs that
     * differ only in punctuation ("my-shop" and "my_shop") produce the same
     * derived name, so the collision the check is here to catch can happen
     * without anybody having typed a name at all.
     */
    private function packagedThemeName(string $folder, string $slug): string
    {
        $file = $folder.'/'.ThemeSettings::FILE;

        if (is_file($file) && is_readable($file)) {
            $data = json_decode((string) file_get_contents($file), true);

            // A package whose theme.json is a list, or a JSON scalar, has no name
            // to read out of it — the same shapes ThemeSettings::all() refuses,
            // and for the same reason: nothing here can be trusted to be a name.
            if (is_array($data) && ($data === [] || ! array_is_list($data))) {
                $name = $data['name'] ?? null;

                if (is_string($name) && trim($name) !== '') {
                    return trim($name);
                }
            }
        }

        return ucwords(str_replace(['-', '_'], ' ', $slug));
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
        $selectedSlug = $this->selectedSlug();

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
                    // A theme with templates but no route file registers no
                    // storefront URLs at all, so picking it gives a site that 404s
                    // on every page — including the homepage. The theme is
                    // installed perfectly correctly; it is just missing the one
                    // file that says "this is my home page", and nothing on the
                    // picker would otherwise say so. Where it *is* matters too, so
                    // a theme without one is reported as the path to create rather
                    // than as null — beside its own templates, which is where a
                    // theme created or installed from here keeps it.
                    'hasRoutes' => Themes::routeFileExists($slug),
                    'routeFile' => Themes::routeFile($slug) ?? Themes::path().'/'.$slug.'/routes/web.php',
                ]];
            })
            ->all();

        return view('livewire.admin.theme-settings.index', [
            'themes' => $themes,
            'themeCards' => $themeCards,
            'activeTheme' => Themes::active(),
            'selectedSlug' => $selectedSlug,
            'selectedHasSettings' => Themes::hasSettings($selectedSlug),
            // What the "Create theme.json" button acts on, and whether the
            // Theme Settings card has to explain a missing file.
            'settingsFilePath' => ThemeSettings::file($selectedSlug),
            'settingsFileExists' => ThemeSettings::exists($selectedSlug),
            'settingsFileCount' => count(ThemeSettings::settings($selectedSlug)),
            // The number a blank serial number on the form will be given, so the
            // field can say what it will become instead of leaving the owner to
            // save and find out.
            'nextSn' => Themes::nextSn(),
        ])->layout('layouts.admin', ['title' => 'Theme Settings']);
    }

    /**
     * The site-wide settings keys this screen owns. Kept in sync with the field
     * widgets in the blade view; the homepage image keys are read by the
     * storefront home template (see
     * themes/ecommerce/home.blade.php).
     *
     * Deliberately no `theme_*` key: those belong to a theme's own
     * theme.json, not to this table (see saveThemeValues()).
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
     * Every theme's own settings.blade.php, read once per request.
     *
     * The three discovery methods below all grep the same handful of files, and
     * a single save asks for each of them, so the reads are memoised rather than
     * repeated.
     *
     * @return array<int, string>
     */
    private function settingsDeclarations(): array
    {
        return $this->declarations ??= collect(
            glob(base_path('themes/*/settings.blade.php')) ?: []
        )
            ->map(fn (string $file) => (string) file_get_contents($file))
            ->values()
            ->all();
    }

    /**
     * Every single-value theme field a theme's own settings.blade.php declares —
     * i.e. any key using the "theme_{slug}_..." prefix, bound as
     * `wire:model="settings.theme_*"`. Hydrated into the form on mount and written
     * back to that theme's theme.json on save, so each theme's fields live
     * under its own prefix and can never collide with another theme's (or the
     * core keys above).
     *
     * Discovered from the blade rather than from the settings files, which is what
     * makes a brand-new field load blank and persist like any other: a field is
     * configured because the theme declares it, not because something already
     * happens to be stored under its name.
     *
     * @return array<int, string>
     */
    private function declaredScalarKeys(): array
    {
        $declared = [];

        foreach ($this->settingsDeclarations() as $source) {
            preg_match_all('/(?:settings\.|[\'"])(theme_[a-z0-9_]+)/', $source, $matches);
            array_push($declared, ...$matches[1]);
        }

        // A repeater's key matches the pattern above too, because the theme names
        // it the same way. It is a list, not a text field, so it is excluded here
        // and lives only in $repeaters — otherwise the form would bind one list
        // twice and save would write it back as a string.
        return array_values(array_unique(array_diff($declared, $this->declaredRepeaterKeys())));
    }

    /**
     * Every theme field that holds a *list* of rows, discovered the same way
     * declaredScalarKeys() discovers scalar ones: a theme declares one by
     * rendering <x-admin-repeatable-fields setting-key="theme_{slug}_*">, and this
     * screen grows the matching add/remove/reorder UI for it.
     *
     * The marker is a distinct attribute rather than a plain `key="theme_..."`
     * because that is exactly how a *scalar* theme setting is declared, and the
     * two must not be confused: loading a repeater's key into the scalar
     * $settings bag would have save() write its rows back through the text bag
     * and wipe them. That is why declaredScalarKeys() subtracts these.
     *
     * @return array<int, string>
     */
    private function declaredRepeaterKeys(): array
    {
        $declared = [];

        foreach ($this->settingsDeclarations() as $source) {
            preg_match_all('/setting-key=[\'"](theme_[a-z0-9_]+)[\'"]/', $source, $matches);
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

        foreach ($this->settingsDeclarations() as $source) {
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
     * Which installed theme a `theme_{slug}_*` key belongs to — the one question
     * that decides which theme.json a value is written to.
     *
     * Slugs may contain underscores, so `theme_a_b_c` could in principle belong to
     * a theme `a` or to a theme `a_b`; the longest matching slug wins, which is
     * the only reading that cannot hand a field to a theme that never declared
     * it. A key matching no installed theme returns an empty string, and the
     * caller skips it — a theme whose folder has been deleted is not somewhere
     * to write its values.
     */
    private function themeSlugFor(string $key): string
    {
        $slug = '';

        foreach (array_keys(Themes::all()) as $candidate) {
            if (str_starts_with($key, 'theme_'.$candidate.'_') && strlen($candidate) > strlen($slug)) {
                $slug = $candidate;
            }
        }

        return $slug;
    }

    /**
     * The theme whose settings the card is currently showing: the one picked in
     * Site Design, falling back to the live theme if the pick is not (or is not
     * yet) one of the installed themes. Same expression render() uses, so the
     * "Create theme.json" button always acts on the theme the form above it
     * belongs to.
     */
    private function selectedSlug(): string
    {
        $selected = (string) ($this->settings['site_theme'] ?? '');

        return array_key_exists($selected, Themes::all()) ? $selected : Themes::active();
    }
}
