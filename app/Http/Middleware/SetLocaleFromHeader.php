<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    private const SUPPORTED_LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = strtolower(substr($request->header('Accept-Language', ''), 0, 2));

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
