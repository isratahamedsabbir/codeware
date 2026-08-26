<?php

namespace App\Support;

use DateTimeZone;

class Timezones
{
    /**
     * Every IANA timezone identifier, grouped by region (e.g. "Asia" => ["Asia/Dhaka", ...])
     * for rendering as <optgroup> options in a timezone picker.
     *
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            $region = str_contains($identifier, '/') ? strtok($identifier, '/') : 'Other';
            $groups[$region][] = $identifier;
        }

        ksort($groups);

        return $groups;
    }
}
