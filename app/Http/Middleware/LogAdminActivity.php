<?php

namespace App\Http\Middleware;

use App\Support\AdminActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    /**
     * Record a page visit for tracked admins after the response is ready.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() === 'GET' && AdminActivity::isTracked($request->user())) {
            AdminActivity::log(
                action: 'visit',
                url: $request->fullUrl(),
                method: 'GET',
            );
        }

        return $response;
    }
}
