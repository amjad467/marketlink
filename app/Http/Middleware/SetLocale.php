<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['ku', 'ar'];

        // If explicitly requested via query parameter ?lang=ku / ?lang=ar
        if ($request->has('lang') && in_array($request->query('lang'), $supportedLocales, true)) {
            $locale = $request->query('lang');
            session(['locale' => $locale]);
        } elseif (session()->has('locale') && in_array(session('locale'), $supportedLocales, true)) {
            $locale = session('locale');
        } else {
            $locale = config('app.locale', 'ku');
        }

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = 'ku';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
