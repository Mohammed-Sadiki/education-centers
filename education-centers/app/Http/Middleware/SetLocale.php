<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @var array<int, string>
     */
    protected const SUPPORTED_LOCALES = ['fr', 'ar'];

    protected const DEFAULT_LOCALE = 'fr';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        Session::put('locale', $locale);

        Carbon::setLocale($locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        // 1. URL query param (?lang=ar) — immediate switch.
        if ($request->has('lang')) {
            $lang = $request->input('lang');
            if (is_string($lang) && in_array($lang, self::SUPPORTED_LOCALES, true)) {
                if ($user = $request->user()) {
                    $user->updateQuietly(['locale' => $lang]);
                }
                Session::put('locale', $lang);
                Session::put('locale_explicit', true);

                return $lang;
            }
        }

        // 2. Authenticated user preference wins over a guest session value.
        if ($user = $request->user()) {
            $userLocale = $user->locale ?? self::DEFAULT_LOCALE;
            if (in_array($userLocale, self::SUPPORTED_LOCALES, true)) {
                return $userLocale;
            }
        }

        // 3. Session.
        if (Session::has('locale') && in_array(Session::get('locale'), self::SUPPORTED_LOCALES, true)) {
            return Session::get('locale');
        }

        // 4. Long-lived cookie for guests.
        $cookieLocale = $request->cookie('locale');
        if (is_string($cookieLocale) && in_array($cookieLocale, self::SUPPORTED_LOCALES, true)) {
            return $cookieLocale;
        }

        // 5. Default.
        return self::DEFAULT_LOCALE;
    }
}
