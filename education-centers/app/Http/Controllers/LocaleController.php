<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Supported application locales.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_LOCALES = ['fr', 'ar'];

    /**
     * Switch the application locale and persist the preference.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::SUPPORTED_LOCALES, true), 404);

        App::setLocale($locale);
        $request->session()->put('locale', $locale);
        $request->session()->put('locale_explicit', true);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return redirect()
            ->back(fallback: route('home'))
            ->withCookie(cookie()->forever('locale', $locale));
    }
}
