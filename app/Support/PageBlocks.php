<?php

namespace App\Support;

/**
 * Custom widgets bolted onto a specific standalone page (by slug) — things a
 * CmsSection can't express, like the contact form. Registered once here and
 * every theme's page.blade.php renders whatever's registered for the current
 * page's slug, so adding a new one is a one-line change instead of editing
 * every theme's page.blade.php by hand.
 */
class PageBlocks
{
    /** @var array<string, string> slug => Livewire component name */
    public const ALL = [
        'contact' => 'frontend.contact-form',
    ];

    public static function for(string $slug): ?string
    {
        return self::ALL[$slug] ?? null;
    }
}
