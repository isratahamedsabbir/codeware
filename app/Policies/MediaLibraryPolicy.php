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
     * Media Library is a content screen, so both admin tiers (Admin, Staff) can use
     * it — Staff via the granular permission, Admin unconditionally.
     */
    private function hasAccess(User $user, string $permission): bool
    {
        if (Gate::forUser($user)->allows('access-admin-system')) {
            return true;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
