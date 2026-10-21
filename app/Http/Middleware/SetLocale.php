<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = 'en'): Response
    {
        abort_unless(array_key_exists($locale, config('motogrip.locales')), 404);
        app()->setLocale($locale);

        return $next($request);
    }
}
