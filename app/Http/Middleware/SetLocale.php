<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set the locale from the visitor's saved choice, or from their browser.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $savedLocale = $request->cookie('locale');

        $locale = is_string($savedLocale) ? Locale::tryFrom($savedLocale) : null;

        app()->setLocale($locale->value ?? $request->getPreferredLanguage(array_column(Locale::cases(), 'value')));

        return $next($request);
    }
}
