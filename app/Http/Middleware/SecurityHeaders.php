<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure() && app()->isProduction()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The Vite dev server needs looser rules, so CSP is skipped locally. Alpine evaluates expressions, hence 'unsafe-eval'.
        if (config('motogrip.csp_enabled') && ! app()->environment('local')) {
            $h->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-eval' https://www.googletagmanager.com https://pagead2.googlesyndication.com",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https:",
                "font-src 'self' data:",
                "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://pagead2.googlesyndication.com",
                "frame-src https://googleads.g.doubleclick.net https://tpc.googlesyndication.com",
                "object-src 'none'", "base-uri 'self'", "form-action 'self'", "frame-ancestors 'self'",
            ]));
        }

        return $response;
    }
}
