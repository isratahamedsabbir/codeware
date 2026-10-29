<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\AdminFont;
use App\Support\CustomCode;
use App\Support\EnvFile;
use App\Support\Telegram;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Index extends Component
{
    public array $settings = [];

    public string $activeTab = 'general';

    /**
     * APP_ENV — lives in the .env file, not the Setting table, so it's saved
     * through EnvFile::set() (see saveEnvironment()) rather than the generic
     * save() below. Moved here from the Developer Tools page so it sits next
     * to the rest of the app's identity settings.
     */
    public string $appEnv = '';

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

    /**
     * Page-specific head/body snippets, on top of the global pair in $settings.
     *
     * @var array<int, array{id: string, name: string, head: string, body: string}>
     */
    public array $codeRules = [];

    /**
     * Ids of the $codeRules rows currently expanded — server-side for the same
     * reason as $openConstants: a re-render from typing must not collapse them.
     *
     * @var array<int, string>
     */
    public array $openCodeRules = [];

    /**
     * Telegram bot token and chat id — TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID in
     * .env, like APP_ENV above, so they are saved through EnvFile::set() rather
     * than the settings table (whose rows the public API can serve).
     */
    public string $telegramBotToken = '';

    public string $telegramChatId = '';

    public function mount(): void
    {
        $this->moveLegacyTelegramRows();

        $this->telegramBotToken = (string) (EnvFile::get('TELEGRAM_BOT_TOKEN') ?? '');
        $this->telegramChatId = (string) (EnvFile::get('TELEGRAM_CHAT_ID') ?? '');

        $this->codeRules = CustomCode::rules();
        $this->loadSettings();
        $this->loadConstants();
        $this->appEnv = EnvFile::get('APP_ENV', config('app.env')) ?? config('app.env');
    }

    protected function loadSettings(): void
    {
        $rows = Setting::all();

        foreach ($rows as $setting) {
            // Watermark is configured from the Media Library now, not here — it
            // must not be re-written by this page's save(), so leave it out.
            if (str_starts_with($setting->key, 'watermark_')) {
                continue;
            }

            // The page-specific custom code rules are edited as $codeRules, and
            // saved from there — left in $settings, the stale JSON string would
            // be written back over them by persistGeneralSettings().
            if ($setting->key === CustomCode::SETTING) {
                continue;
            }

            // Boolean settings are stored as the string "0"/"1" (no cast on the
            // Setting model). Left as a string, a checkbox bound to it renders
            // checked no matter what — JS truthiness treats "0" as true, unlike
            // PHP. Cast to a real boolean so the checkbox reflects the stored value.
            $this->settings[$setting->key] = $setting->type === 'boolean'
                ? (bool) $setting->value
                : ($setting->value ?? '');
        }

        // The row is seeded, but a database that predates the setting has none —
        // and the select would then render with nothing selected, which reads
        // as "unset" rather than as the deliberate default. Default it to the
        // system font, the same value the seeder writes. A saved font whose
        // folder has since been deleted is shown as the system font too, which
        // is what the panel is already rendering in.
        // Same for the header app-name label: a database that predates it has no
        // row, and the header treats "no row" as on, so the checkbox must too.
        // Cast explicitly: a row first written by save() (not the seeder) has
        // type "string", so the loop above would leave "0" as a truthy string.
        $this->settings['app_name_label_enabled'] = (bool) ($this->settings['app_name_label_enabled'] ?? true);

        $this->settings['admin_font'] = AdminFont::normalize($this->settings['admin_font'] ?? AdminFont::SYSTEM);
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

    public function addCodeRule(): void
    {
        $rule = ['id' => CustomCode::newId(), 'name' => '', 'head' => '', 'body' => ''];
        $this->codeRules[] = $rule;
        $this->openCodeRules[] = $rule['id'];
    }

    public function toggleCodeRule(string $id): void
    {
        $this->openCodeRules = in_array($id, $this->openCodeRules, true)
            ? array_values(array_diff($this->openCodeRules, [$id]))
            : [...$this->openCodeRules, $id];
    }

    public function removeCodeRule(int $index): void
    {
        $removed = $this->codeRules[$index]['id'] ?? null;
        unset($this->codeRules[$index]);
        $this->codeRules = array_values($this->codeRules);
        $this->openCodeRules = array_values(array_diff($this->openCodeRules, [$removed]));
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
            'codeRules.*.name' => 'nullable|string|max:100',
            'codeRules.*.head' => 'nullable|string',
            'codeRules.*.body' => 'nullable|string',
        ]);

        $this->persistGeneralSettings();

        if (! $this->persistTelegram()) {
            // Everything else did save; say which part did not rather than
            // reloading over the message.
            $this->dispatch('notify', type: 'error', message: 'Settings saved, but the Telegram details could not be written to .env.');

            return;
        }

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
     * The token and chat id were briefly stored as settings rows. Carry any such
     * value over to .env (unless .env already has one) and drop the rows, so a
     * secret is not left in the table — and so the rows do not turn up as plain
     * text fields in the generic settings list.
     */
    private function moveLegacyTelegramRows(): void
    {
        $rows = Setting::whereIn('key', ['telegram_bot_token', 'telegram_chat_id'])->pluck('value', 'key');

        if ($rows->isEmpty()) {
            return;
        }

        $carry = [];

        foreach (['telegram_bot_token' => 'TELEGRAM_BOT_TOKEN', 'telegram_chat_id' => 'TELEGRAM_CHAT_ID'] as $key => $envKey) {
            if (filled($rows[$key] ?? null) && blank(EnvFile::get($envKey))) {
                $carry[$envKey] = (string) $rows[$key];
            }
        }

        try {
            if ($carry !== []) {
                EnvFile::set($carry);
                Artisan::call('config:clear');
            }
        } catch (\RuntimeException) {
            // .env is not writable: keep the rows rather than lose the values.
            return;
        }

        Setting::forget('telegram_bot_token');
        Setting::forget('telegram_chat_id');
    }

    /**
     * Writes the Telegram card to .env. EnvFile only rewrites a key whose value
     * changed, and the config cache is cleared only then, so saving the page
     * with the card untouched does neither.
     */
    private function persistTelegram(): bool
    {
        $values = [
            'TELEGRAM_BOT_TOKEN' => trim($this->telegramBotToken),
            'TELEGRAM_CHAT_ID' => trim($this->telegramChatId),
        ];

        if ($values === [
            'TELEGRAM_BOT_TOKEN' => (string) EnvFile::get('TELEGRAM_BOT_TOKEN'),
            'TELEGRAM_CHAT_ID' => (string) EnvFile::get('TELEGRAM_CHAT_ID'),
        ]) {
            return true;
        }

        try {
            EnvFile::set($values);
        } catch (\RuntimeException) {
            return false;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Telegram settings updated');

        return true;
    }

    /**
     * Sends a message with what is typed in the Telegram card, saved or not, so
     * a wrong token or chat id is found before it silently drops a real contact.
     */
    public function sendTelegramTest(): void
    {
        $sent = Telegram::send(
            'Hello from '.config('app.name').' — Telegram is connected.',
            $this->telegramBotToken,
            $this->telegramChatId,
        );

        $this->dispatch('notify', type: $sent ? 'success' : 'error', message: $sent
            ? 'Test message sent. Check your Telegram chat.'
            : 'Could not send. Check the bot token and chat id (and that the bot is in the chat).');
    }

    public function confirmSaveEnvironment(): void
    {
        $this->validate([
            'appEnv' => 'required|in:local,staging,production,testing,developer',
        ]);

        $this->dispatch('open-modal', name: 'settings-env-confirm');
    }

    /**
     * Writes APP_ENV straight to the .env file, same mechanism (and risk) as
     * the rest of the Developer Tools page — see App\Livewire\Admin\Env\Index.
     */
    public function saveEnvironment(): void
    {
        try {
            EnvFile::set(['APP_ENV' => $this->appEnv]);
        } catch (\RuntimeException $e) {
            $this->dispatch('close-modal', name: 'settings-env-confirm');
            $this->dispatch('notify', message: 'Could not update the environment: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Environment updated');

        $this->dispatch('close-modal', name: 'settings-env-confirm');
        session()->flash('success', 'Environment updated. Configuration cache cleared.');
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
            // The panel typeface is stored as a theme slug, so it is validated
            // against the options that exist now: a value whose theme is gone
            // (uninstalled, or hand-edited) becomes the system default rather
            // than a dangling reference that resolves to nothing.
            Setting::set($key, $key === 'admin_font' ? AdminFont::normalize($value) : $value);
        }

        $constants = collect($this->constants)->filter(fn (array $pair) => filled($pair['key'] ?? null))->values()->all();
        Setting::set('constants', json_encode($constants));

        // Rows with no code in either box are dropped rather than stored empty. The id is what pages
        // point at, so it is kept as-is across saves.
        $rules = collect($this->codeRules)
            ->filter(fn (array $rule) => filled($rule['head'] ?? null) || filled($rule['body'] ?? null))
            ->map(fn (array $rule) => [
                'id' => $rule['id'],
                'name' => trim((string) ($rule['name'] ?? '')),
                'head' => (string) ($rule['head'] ?? ''),
                'body' => (string) ($rule['body'] ?? ''),
            ])
            ->values()
            ->all();
        Setting::set(CustomCode::SETTING, json_encode($rules));
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
            // 'colors' renders hand-rolled as the Backend card in the General tab,
            // not through this generic per-group loop. 'frontend' (site_theme and the
            // theme homepage copy/imagery) lives on the dedicated Theme Settings screen.
            // 'currency' (currency + VAT fields with a live-preview modal) is hand-rendered
            // in the General tab, not through this generic per-group loop. 'other' is
            // hand-rendered in its own tab (the Floating Button card) rather than through
            // this generic per-group loop. 'shop' (shop_enabled) is controlled only via the
            // header toggle (ShopToggle), never a form field here.
            // 'orders' (order_cancellation_cutoff_status) has its own settings modal on
            // the admin Orders screen, same as 'editor' does on Pages — though the
            // editor's own token expiry now lives in .env (PUCK_SESSION) and has no
            // settings row at all; 'editor' is still excluded so a row left over from
            // an older database never surfaces here.
            // 'admin' (admin_font) is hand-rendered in the Backend card above, not
            // through this generic per-group loop — it needs a select, not a plain
            // text input. 'security' is excluded only as a backstop: MFA and
            // reCAPTCHA enforcement moved to the per-role switches on Roles, and
            // the migration deleted those rows, so nothing seeds that group any
            // more — but a row left behind on an older database would otherwise
            // render here as a bare checkbox with no explanation.
            'groupedSettings' => Setting::whereNotIn('group', ['layout', 'seo', 'colors', 'currency', 'frontend', 'other', 'editor', 'custom-code', 'tracking', 'shop', 'orders', 'admin', 'security'])
                ->get()
                ->groupBy('group')
                ->sortBy(fn ($items, $group) => $groupOrder[$group] ?? count($groupOrder)),
            'colorSettings' => Setting::where('group', 'colors')->get(),
            // The panel's typeface options: the OS's own font, or Segoe UI named
            // first. A fixed pair, so this is a constant read and not a per-theme
            // lookup — see App\Support\AdminFont.
            'adminFontOptions' => AdminFont::options(),
        ])->layout('layouts.admin', ['title' => 'Settings']);
    }
}
