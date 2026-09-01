<?php

namespace App\Concerns;

use App\Models\Setting;

/**
 * Adds a per-table "rows per page" selector to a Livewire list component.
 * Pair with Livewire\WithPagination. The selector defaults to the site-wide
 * Settings → General → Pagination value, with a few common step-ups alongside it.
 */
trait HasPerPage
{
    public int $perPage = 10;

    public function mountHasPerPage(): void
    {
        $this->perPage = Setting::perPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<int, int>
     */
    public function perPageOptions(): array
    {
        return array_values(array_unique([Setting::perPage(), 25, 50, 100]));
    }
}
