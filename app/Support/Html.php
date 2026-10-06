<?php

namespace App\Support;

class Html
{
    /**
     * Reduce authored HTML to the 'rich' allowlist in config/purifier.php:
     * no script/iframe/on* attributes, and only http(s)/mailto/tel URLs.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return (string) clean($html, 'rich');
    }
}
