<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?int $userId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $isAdmin = false;

    public array $selectedRoles = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $user = User::findOrFail($id);
            $this->userId = $id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->isAdmin = (bool) $user->is_admin;
            $this->selectedRoles = $user->roles->pluck('name')->toArray();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'isAdmin' => ['boolean'],
        ]);

        $user = $this->userId
            ? User::findOrFail($this->userId)
            : new User;

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_admin = $this->isAdmin;

        if ($this->password) {
            $user->password = $this->password;
        }

        $user->save();

        $user->syncRoles($this->selectedRoles);

        $this->dispatch('notify', message: $this->userId ? 'User updated successfully' : 'User created successfully');

        $this->redirect(route('admin.users'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.form', [
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => $this->userId ? 'Edit User' : 'New User']);
    }
}
