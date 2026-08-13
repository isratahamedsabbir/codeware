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
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(Locale::default());

        return $next($request);
    }
}
