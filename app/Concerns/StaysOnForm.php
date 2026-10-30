<?php

namespace App\Concerns;

/**
 * Saving an admin form keeps the admin on it. A brand-new record moves to its
 * own edit page (so a second click on Save updates it rather than creating a
 * duplicate); an existing one simply stays where it is. Neither goes back to
 * the list — that is what the breadcrumb and the sidebar are for.
 */
trait StaysOnForm
{
    /**
     * @param  array<string, mixed>|int|string  $parameters  the edit route's parameters
     */
    protected function stayOnForm(bool $creating, string $editRoute, array|int|string $parameters): void
    {
        if ($creating) {
            $this->redirect(route($editRoute, $parameters), navigate: true);
        }
    }
}
