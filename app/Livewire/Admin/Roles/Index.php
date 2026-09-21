<?php

namespace App\Livewire\Admin\Roles;

use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use HasPerPage, WithPagination, WithSearch;

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

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'role-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $role = Role::findOrFail($this->deletingId);

            if ($role->name === 'admin') {
                $this->dispatch('notify', message: 'The admin role cannot be deleted');
            } else {
                $role->delete();
                AdminActivity::log('deleted', "Role: {$role->name}");
                $this->dispatch('notify', message: 'Role deleted successfully');
            }

            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'role-delete');
    }

    /**
     * Deactivating a role immediately locks out anyone currently holding it —
     * see the login-time check in FortifyServiceProvider and
     * Vendor\Auth\Login, and the access-admin/access-vendor-portal gates —
     * and also drops their sessions below, so an already-open tab is kicked
     * out rather than merely blocked on its next gated request. The 'admin'
     * role can never be deactivated, same as it can never be deleted — doing
     * so would lock out every plain admin-role account, including whoever
     * just clicked it.
     */
    public function toggleStatus(int $id): void
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'admin') {
            $this->dispatch('notify', message: 'The admin role cannot be deactivated');

            return;
        }

        $newStatus = $role->status === 'active' ? 'inactive' : 'active';
        $role->update(['status' => $newStatus]);

        if ($newStatus === 'inactive') {
            $this->endSessionsForRole($role);
        }

        AdminActivity::log('updated', "Role: {$role->name} — status set to {$newStatus}");
        $this->dispatch('notify', message: 'Role status updated');
    }

    /**
     * Force-logs-out every holder of a just-deactivated role by deleting
     * their session rows outright (SESSION_DRIVER=database) — the sessions
     * table is shared across both hosts, so this reaches a vendor-host
     * session the same way it reaches an admin-host one.
     */
    private function endSessionsForRole(Role $role): void
    {
        $userIds = $role->users()->pluck('users.id');

        if ($userIds->isNotEmpty()) {
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
        }
    }

    public function render()
    {
        return view('livewire.admin.roles.index', [
            'roles' => Role::query()
                ->with('creator')
                ->withCount(['users', 'permissions'])
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Roles']);
    }
}
