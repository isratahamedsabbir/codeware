<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/**
 * The gate in front of everything that writes PHP to the server from the admin
 * panel (plugin and theme install/scaffold). An admin account that is taken over
 * must not turn straight into code execution, so installs are switched off in
 * production unless opted in, re-ask for the password, can be pinned to known
 * package checksums, and are logged and announced to every admin.
 */
class CodeInstall
{
    public static function allowed(): bool
    {
        return (bool) config('security.code_install.allowed');
    }

    /**
     * Returns a message to show the admin when the install must not go ahead,
     * or null when it may. $password is the re-entered account password;
     * $zipPath is the uploaded package, checked against the allowlist if set.
     */
    public static function refusal(?string $password, ?string $zipPath = null, bool $needsPassword = true): ?string
    {
        if (! self::allowed()) {
            return 'Installing code from the admin panel is disabled on this server (ALLOW_PLUGIN_UPLOAD). Deploy it through your release pipeline.';
        }

        if ($needsPassword) {
            $user = auth()->user();

            if (! $user || ! filled($password) || ! Hash::check($password, $user->password)) {
                return 'Enter your current password to confirm this install.';
            }
        }

        $allowed = config('security.code_install.sha256', []);

        if ($zipPath !== null && $allowed !== [] && ! in_array(strtolower((string) hash_file('sha256', $zipPath)), array_map('strtolower', $allowed), true)) {
            return 'This package is not on the list of approved checksums (PLUGIN_ALLOWED_SHA256).';
        }

        return null;
    }

    /** Logs the install and tells every admin about it. */
    public static function record(string $action, string $description): void
    {
        AdminActivity::log($action, $description);

        $actor = auth()->user()?->email ?? 'unknown';

        Notification::send(
            User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get(),
            new AdminAlert('Code installed from admin panel', "{$description} (by {$actor})", route('admin.history')),
        );
    }
}
