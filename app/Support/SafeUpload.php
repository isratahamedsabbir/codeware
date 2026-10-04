<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * The extension to store an upload under.
 *
 * Not the one the browser claimed. `getClientOriginalExtension()` (and its
 * shorter alias `clientExtension()`) is a request field: nothing about it is
 * derived from the bytes, so `curl -F "photo=@payload.php;filename=photo.php"`
 * names the stored file `*.php` and the public disk now holds a script PHP-FPM
 * will happily run. Every site that hit that was a `storeAs()` with a
 * hand-built name — admin/vendor/customer profile photos, user NID documents,
 * theme logos, vendor product images.
 *
 * `guessExtension()` comes from Symfony's mime guesser, i.e. the content, so
 * the stored extension is never attacker-chosen. The `mimes:`/`image` rules
 * those call sites already validate with are themselves content-based, so by
 * the time this runs the extension is known to be one the caller allowed too.
 *
 * The `bin` fallback is for content finfo cannot type at all (empty or
 * garbage); it is deliberately not the client extension, so an unidentifiable
 * upload cannot pick its own suffix.
 */
final class SafeUpload
{
    public static function extension(UploadedFile $file): string
    {
        return $file->guessExtension() ?: 'bin';
    }
}
