<?php

namespace App\Livewire\Admin\Users;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\User;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use HasPerPage, WithPagination, WithSearch;

    public string $roleFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'user-delete');
    }

    /**
     * Deletes the user and drops their sessions (same as toggleBlock below),
     * so an already-open tab is kicked out immediately rather than merely
     * failing on its next request.
     */
    public function delete(): void
    {
        if ($this->deletingId) {
            $user = User::findOrFail($this->deletingId);

            if ($user->id === 1) {
                $this->dispatch('notify', message: 'The primary admin account cannot be deleted');
            } elseif ($user->id === auth()->id()) {
                $this->dispatch('notify', message: 'You cannot delete your own account');
            } else {
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->delete();
                AdminActivity::log('deleted', "User: {$user->email}");
                $this->dispatch('notify', message: 'User deleted successfully');
            }

            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'user-delete');
    }

    /**
     * Blocking a user immediately locks the account out everywhere (see
     * EnsureUserIsNotBlocked, and the login-time checks in
     * FortifyServiceProvider and Vendor\Auth\Login) and drops their
     * sessions below, so an already-open tab is kicked out rather than
     * merely blocked on its next request. Blocking yourself is disallowed —
     * doing so would lock out the very account performing the action.
     */
    public function toggleBlock(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', message: 'You cannot block your own account');

            return;
        }

        $user = User::findOrFail($id);
        $user->is_blocked = ! $user->is_blocked;
        $user->save();

        if ($user->is_blocked) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AdminActivity::log('updated', ($user->is_blocked ? 'Blocked' : 'Unblocked')." user: {$user->email}");
        $this->dispatch('notify', message: $user->is_blocked ? 'User blocked' : 'User unblocked');
    }

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => User::query()
                ->with('roles')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->when($this->roleFilter, fn ($q) => $q->role($this->roleFilter))
                ->orderBy('id')
                ->paginate($this->perPage),
            'roles' => Role::orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Users']);
    }
}
