<?php

namespace App\Policies;

use App\Models\MediaLibrary;
use App\Models\User;

class MediaLibraryPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, MediaLibrary $mediaLibrary): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, MediaLibrary $mediaLibrary): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, MediaLibrary $mediaLibrary): bool
    {
        return (bool) $user->is_admin;
    }
}
