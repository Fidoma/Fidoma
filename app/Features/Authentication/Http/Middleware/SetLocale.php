<?php

namespace App\Features\Authentication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (in_array($locale, ['de', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
