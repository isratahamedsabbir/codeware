<?php

namespace App\Livewire\Admin\ActivityLogs;

use App\Concerns\HasPerPage;
use App\Models\AdminActivityLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public string $userFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->userFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = AdminActivityLog::with('user')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('url', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%");
            }))
            ->when($this->actionFilter, fn ($q) => $q->where('action', $this->actionFilter))
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->latest();

        return view('livewire.admin.activity-logs.index', [
            'logs' => $query->paginate($this->perPage),
            'admins' => User::query()
                ->where('is_admin', true)
                ->orWhereHas('roles')
                ->orderBy('name')
                ->get(),
            'actions' => ['login', 'logout', 'visit', 'created', 'updated', 'deleted'],
        ])->layout('layouts.admin', ['title' => 'Admin History']);
    }
}
