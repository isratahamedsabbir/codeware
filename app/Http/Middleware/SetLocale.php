<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the globally configured locale to every web request.
     *
     * The locale lives in the `app_locale` setting, so it also drives what
     * spatie/laravel-translatable returns for un-suffixed model attributes.
     *
     * A `?lang=` query param lets a single visitor's session preview the site
     * in a different language (e.g. the public theme's EN/BN toggle) without
     * touching the `app_locale` setting — that setting is global and switches
     * the language for every visitor, so it's deliberately left untouched here.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');

        if (is_string($requested) && Locale::isSupported($requested)) {
            $request->session()->put('frontend_locale', $requested);
        }

        App::setLocale($request->session()->get('frontend_locale') ?: Locale::default());

        return $next($request);
    }
}
