<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

if (! function_exists('is_rtl')) {
    /**
     * Determine whether the current locale is written right-to-left.
     */
    function is_rtl(?string $locale = null): bool
    {
        return ($locale ?? App::getLocale()) === 'ar';
    }
}

if (! function_exists('current_dir')) {
    /**
     * Return the HTML direction attribute for the current locale.
     */
    function current_dir(?string $locale = null): string
    {
        return is_rtl($locale) ? 'rtl' : 'ltr';
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date using the active locale (French or Arabic month names).
     */
    function format_date(mixed $date, string $format = 'd M Y', string $fallback = '—'): string
    {
        if ($date === null || $date === '') {
            return $fallback;
        }

        try {
            $carbon = $date instanceof DateTimeInterface
                ? Carbon::instance($date)
                : Carbon::parse((string) $date);

            return $carbon->locale(App::getLocale())->translatedFormat($format);
        } catch (Throwable) {
            return $fallback;
        }
    }
}

if (! function_exists('locale_name')) {
    /**
     * Human readable name of a supported locale.
     */
    function locale_name(?string $locale = null): string
    {
        return match ($locale ?? App::getLocale()) {
            'ar' => __('العربية'),
            default => __('Français'),
        };
    }
}
