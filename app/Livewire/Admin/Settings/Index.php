<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    public string $activeTab = 'general';

    /** @var array<int, array{key: string, type: string, value: string}> */
    public array $constants = [];

    /**
     * Indices of $constants rows currently expanded in the UI — tracked
     * server-side (rather than client-only Alpine state per row) so a Livewire
     * re-render from an unrelated input (e.g. typing in another row's Key
     * field) can't reset it: deriving "open" from whether the key is blank
     * on every render meant collapsed state flip-flopped as soon as a key
     * stopped being blank, and a re-render reset every row at once.
     *
     * @var array<int, int>
     */
    public array $openConstants = [];

    public function mount(): void
    {
        $this->loadSettings();
        $this->loadConstants();
    }

    protected function loadSettings(): void
    {
        $rows = Setting::all();

        foreach ($rows as $setting) {
            // Boolean settings are stored as the string "0"/"1" (no cast on the
            // Setting model). Left as a string, a checkbox bound to it renders
            // checked no matter what — JS truthiness treats "0" as true, unlike
            // PHP. Cast to a real boolean so the checkbox reflects the stored value.
            $this->settings[$setting->key] = $setting->type === 'boolean'
                ? (bool) $setting->value
                : ($setting->value ?? '');
        }
    }

    protected function loadConstants(): void
    {
        $this->constants = collect(json_decode(Setting::get('constants', '[]') ?: '[]', true) ?: [])
            ->map(fn (array $pair) => [...$pair, 'type' => in_array($pair['type'] ?? null, ['textarea', 'file'], true) ? $pair['type'] : 'textarea'])
            ->all();
    }

    public function addConstant(): void
    {
        $this->constants[] = ['key' => '', 'type' => 'textarea', 'value' => ''];
        $this->openConstants[] = array_key_last($this->constants);
    }

    public function removeConstant(int $index): void
    {
        unset($this->constants[$index]);
        $this->constants = array_values($this->constants);

        $this->openConstants = collect($this->openConstants)
            ->reject(fn ($i) => $i === $index)
            ->map(fn ($i) => $i > $index ? $i - 1 : $i)
            ->unique()
            ->values()
            ->all();
    }

    public function setConstantType(int $index, string $type): void
    {
        if (! array_key_exists($index, $this->constants) || ! in_array($type, ['textarea', 'file'], true)) {
            return;
        }

        $this->constants[$index]['type'] = $type;
    }

    public function toggleConstant(int $index): void
    {
        if (in_array($index, $this->openConstants, true)) {
            $this->openConstants = array_values(array_diff($this->openConstants, [$index]));
        } else {
            $this->openConstants[] = $index;
        }
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^constants\.\d+\.key$/', $name)) {
            $sanitized = preg_replace('/[^A-Za-z0-9_]/', '', preg_replace('/\s+/', '_', trim($value)));

            if ($sanitized !== $value) {
                data_set($this, $name, $sanitized);
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'constants' => ['array', function (string $attribute, mixed $value, \Closure $fail) {
                $keys = collect($value)->pluck('key')->filter()->map(fn ($key) => strtolower(trim($key)));

                if ($keys->count() !== $keys->unique()->count()) {
                    $fail('Constant keys must be unique.');
                }
            }],
            'constants.*.key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]*$/'],
            'constants.*.type' => 'nullable|in:textarea,file',
            'constants.*.value' => 'nullable|string|max:1000',
        ]);

        $this->persistGeneralSettings();

        // A real browser reload of the current page, rather than a dispatched
        // toast, so the whole admin shell re-renders on a fresh request —
        // header icons, colors, and anything else read from Setting::get()
        // in layouts.admin only reflect a change after that, since Livewire's
        // own re-render never touches the surrounding layout. (Redirecting to
        // request()->fullUrl() would resolve to the Livewire update endpoint
        // itself, not the page — hence a plain client-side reload instead.)
        session()->flash('success', 'Settings saved.');
        $this->js('window.location.reload()');
    }

    /**
     * Persists every plain (DB-backed) Setting row. The .env-backed fields on
     * the standalone Env page (see App\Livewire\Admin\Env\Index) go through
     * EnvFile::set() instead, not this.
     */
    private function persistGeneralSettings(): void
    {
        foreach ($this->settings as $key => $value) {
            Setting::set($key, $value);
        }

        $constants = collect($this->constants)->filter(fn ($pair) => filled($pair['key'] ?? null))->values()->all();
        Setting::set('constants', json_encode($constants));
    }

    public function render()
    {
        // Section order within the General tab — not the DB row order, which isn't
        // guaranteed without an ORDER BY. General and Images render side by side (see
        // the view), so their relative order here doesn't matter; anything not listed
        // falls to the end in whatever order it comes. Pagination gets its own card
        // (via its own 'pagination' group) rather than sharing General's.
        $groupOrder = ['general' => 0, 'pagination' => 1, 'images' => 2, 'localization' => 3, 'newsletter' => 4];

        return view('livewire.admin.settings.index', [
            // 'frontend' (site_theme) and 'colors' live under the Theme tab, not here.
            // 'other' is hand-rendered in its own tab (the Floating Button card) rather
            // than through this generic per-group loop. 'shop' (shop_enabled) is
            // controlled only via the header toggle (ShopToggle), never a form field here.
            // 'orders' (order_cancellation_cutoff_status) has its own settings modal on
            // the admin Orders screen, same as 'editor' (puck_session_minutes) does on Pages.
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'seo', 'colors', 'currency', 'frontend', 'other', 'editor', 'custom-code', 'tracking', 'shop', 'orders'])
                ->get()
                ->groupBy('group')
                ->sortBy(fn ($items, $group) => $groupOrder[$group] ?? count($groupOrder)),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            'currencySettings' => Setting::where('group', 'currency')->get(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
