<?php

namespace App\Policies;

use App\Models\MediaLibrary;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class MediaLibraryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user, 'view media');
    }

    public function view(User $user, MediaLibrary $mediaLibrary): bool
    {
        return $this->hasAccess($user, 'view media');
    }

    public function create(User $user): bool
    {
        return $this->hasAccess($user, 'upload media');
    }

    public function update(User $user, MediaLibrary $mediaLibrary): bool
    {
        return $this->hasAccess($user, 'upload media');
    }

    public function delete(User $user, MediaLibrary $mediaLibrary): bool
    {
        return $this->hasAccess($user, 'delete media');
    }

    /**
     * Media Library is a content screen, so all three admin tiers (Super Admin, Admin,
     * Staff) can use it — Staff via the granular permission, Admin/Super Admin
     * unconditionally. Previously this only ever checked is_admin, which meant a
     * Spatie 'admin'-role user (not is_admin=true) was wrongly locked out.
     */
    private function hasAccess(User $user, string $permission): bool
    {
        if ((bool) $user->is_admin || Gate::forUser($user)->allows('access-admin-system')) {
            return true;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
