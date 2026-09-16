<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-request UI language. Lao-first (default 'lo'); a user may switch to 'en'
 * via the top-right language toggle (route locale.switch), which stores the
 * choice in the session. Missing 'en' strings fall back to 'lo' (set in
 * AppServiceProvider) so nothing ever renders a raw translation key.
 */
class SetLocale
{
    /** Languages the UI toggle offers. */
    public const SUPPORTED = ['lo', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession()
            ? $request->session()->get('app_locale', 'lo')
            : 'lo';

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'lo';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
