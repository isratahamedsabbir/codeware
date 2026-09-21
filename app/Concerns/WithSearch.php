<?php

namespace App\Concerns;

/**
 * Adds the standard free-text search box to a Livewire list component —
 * the `search` property itself (hydrated from the search input) plus the
 * `updatedSearch` hook that resets pagination whenever it changes.
 */
trait WithSearch
{
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}
