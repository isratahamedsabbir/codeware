<?php

namespace App\Concerns;

/**
 * Adds row multi-selection to a Livewire list component: the `selectedIds`
 * property and the Ctrl/Cmd+click (or row checkbox) toggle that fills it.
 * Everything after selection — bulk delete, export, etc. — stays in the
 * consuming component, since it is model-specific.
 */
trait HasBulkSelection
{
    /** @var array<int, int> */
    public array $selectedIds = [];

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

            return;
        }

        $this->selectedIds[] = $id;
    }
}
