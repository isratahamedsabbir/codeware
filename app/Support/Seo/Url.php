<?php

namespace App\Support\Seo;

use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * URL building and normalisation for canonicals, hreflang and sitemaps.
 *
 * The problem this exists to solve: `url()->current()` echoes back whatever the
 * request arrived as. So the same page would self-canonicalise to a different
 * address over http vs https, with vs without www, and with a tracking query
 * string attached — three URLs, one page, and the crawler picks one. Everything
 * a page says about its own address therefore goes through here, so there is one
 * spelling of a URL in the whole application.
 */
final class Url
{
    /**
     * The origin every canonical, hreflang and sitemap URL is built on: the
     * admin-configured site URL when there is one, else the request's own.
     *
     * Configuring it is what makes http/https and www/non-www unify — the
     * variants stop producing different canonicals even before the redirect in
     * RedirectToCanonicalHost sends the visitor to the preferred one.
     */
    public static function origin(?Request $request = null): string
    {
        $configured = trim((string) Setting::get('seo_site_url', ''));

        if ($configured !== '') {
            return self::schemeHost($configured);
        }

        $request ??= request();

        return self::schemeHost($request->getSchemeAndHttpHost());
    }

    /**
     * A path, absolute and normalised: leading slash, no trailing slash (except
     * the root), no query string, no fragment.
     */
    public static function path(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    public static function url(string $path, ?Request $request = null): string
    {
        return self::origin($request).self::path($path);
    }

    /**
     * Puts a URL into the one spelling this application uses: https when
     * configured to, lower-cased host, no default port, no query, no fragment, no
     * trailing slash. Safe to run on something already normalised.
     */
    public static function normalize(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return self::origin();
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            // Not absolute — treat it as a path so a half-typed canonical_base
            // still yields something usable rather than a broken <link>.
            return self::url($url);
        }

        $scheme = mb_strtolower($parts['scheme'] ?? 'https');

        if ($scheme === 'http' && self::forceHttps()) {
            $scheme = 'https';
        }

        // A default port for the scheme carries no meaning and Google treats
        // example.com:443 and example.com as different URLs.
        $port = $parts['port'] ?? null;
        $default = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        $host = mb_strtolower($parts['host']).($port && ! $default ? ':'.$port : '');

        return $scheme.'://'.$host.self::path($parts['path'] ?? '/');
    }

    public static function forceHttps(): bool
    {
        return (bool) config('seo.force_https', false);
    }

    /**
     * scheme://host[:port] with the host lower-cased and a default port dropped.
     */
    private static function schemeHost(string $url): string
    {
        $url = self::normalize($url);

        return (string) (parse_url($url, PHP_URL_SCHEME) ?: 'https').'://'.parse_url($url, PHP_URL_HOST).(parse_url($url, PHP_URL_PORT) ? ':'.parse_url($url, PHP_URL_PORT) : '');
    }
}
