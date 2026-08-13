<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Request;

class AdminActivity
{
    /**
     * Record an entry in the admin activity log.
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?string $url = null,
        ?string $method = null,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        AdminActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'url' => $url ?? Request::fullUrl(),
            'method' => $method ?? Request::method(),
            'ip_address' => $ip ?? Request::ip(),
            'user_agent' => $userAgent ?? Request::userAgent(),
        ]);
    }

    /**
     * Whether the given user should be tracked in the admin activity log.
     */
    public static function isTracked(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) $user->is_admin || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
    }
}
