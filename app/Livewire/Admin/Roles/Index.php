<?php

namespace App\Livewire\Admin\Roles;

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

    /**
     * The two login requirements, keyed by role id and bound straight to the
     * switches in the table. They are not casts on the Role model on purpose:
     * Spatie's Role is a vendor class that 45 files import directly, and
     * replacing it with a project subclass to gain two booleans would mean
     * either re-pointing config('permission.models.role') (and then every
     * static hook registered against the old class stops firing, including the
     * created_by tracking in AppServiceProvider) or casting at every read. Two
     * exists() queries on a page that already lists every role is cheaper than
     * either.
     *
     * @var array<int, bool>
     */
    public array $mfa = [];

    /** @var array<int, bool> */
    public array $recaptcha = [];

    /**
     * Column => the words used in the switch tooltip and the activity log.
     *
     * @var array<string, string>
     */
    private const REQUIREMENTS = [
        'mfa_enabled' => 'MFA',
        'recaptcha_enabled' => 'reCAPTCHA',
    ];

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    /**
     * Livewire calls updated{Property}($value, $key) for an array property.
     *
     * The switch always sends a boolean, but a crafted request can send
     * anything at all, so the value is cast here rather than trusted — the
     * column is a boolean and only booleans belong in it.
     */
    public function updatedMfa(mixed $value, string|int $key): void
    {
        $this->persistRequirement('mfa_enabled', $key, $value);
    }

    public function updatedRecaptcha(mixed $value, string|int $key): void
    {
        $this->persistRequirement('recaptcha_enabled', $key, $value);
    }

    /**
     * Write one switch straight through to the role.
     *
     * Deliberately not deferred to the edit form: an admin holding MFA on for
     * the admin role is protecting the panel right now, and making them open a
     * role, find two switches and press Save to do it is a delay with a cost if
     * they never do.
     */
    private function persistRequirement(string $column, string|int $roleId, mixed $value): void
    {
        $role = Role::findOrFail($roleId);
        $on = (bool) $value;

        if ((bool) $role->{$column} === $on) {
            return;
        }

        $role->update([$column => $on]);

        $label = self::REQUIREMENTS[$column];

        AdminActivity::log('updated', "Role: {$role->name} — {$label} ".($on ? 'required' : 'no longer required'));
        $this->dispatch('notify', message: "{$label} is now ".($on ? 'required' : 'not required')." for {$role->name}");
    }

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
     * see the login-time checks in FortifyServiceProvider, the Vendor/Delivery
     * logins and the API login, and EnsureUserIsNotBlocked on every request —
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
     * table is shared across every host, so this reaches a vendor- or
     * delivery-host session the same way it reaches an admin-host one. Their
     * API tokens and "remember me" tokens go too, so neither the app nor a
     * remembered browser can quietly sign them back in once it is re-enabled.
     * EnsureUserIsNotBlocked is the backstop for any other session driver.
     */
    private function endSessionsForRole(Role $role): void
    {
        $userIds = $role->users()->pluck('users.id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('sessions')->whereIn('user_id', $userIds)->delete();
        DB::table('personal_access_tokens')
            ->where('tokenable_type', (new User)->getMorphClass())
            ->whereIn('tokenable_id', $userIds)
            ->delete();
        DB::table('users')->whereIn('id', $userIds)->update(['remember_token' => null]);
    }

    public function render()
    {
        $roles = Role::query()
            ->with('creator')
            ->withCount(['users', 'permissions'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate($this->perPage);

        // Seeded from the row that was just rendered rather than from the
        // submitted array, so the switch always shows what is actually stored:
        // updatedMfa()/updatedRecaptcha() write first and this reads back after.
        $this->mfa = $roles->mapWithKeys(fn (Role $role) => [$role->id => (bool) $role->mfa_enabled])->all();
        $this->recaptcha = $roles->mapWithKeys(fn (Role $role) => [$role->id => (bool) $role->recaptcha_enabled])->all();

        return view('livewire.admin.roles.index', [
            'roles' => $roles,
        ])->layout('layouts.admin', ['title' => 'Roles']);
    }
}
