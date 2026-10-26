<?php

declare(strict_types=1);
use Illuminate\Support\Facades\App;

if (! function_exists('format_number')) {
    /**
     * Format a number using the active locale.
     *
     * French keeps the historical "1 500,00" output. Arabic uses Arabic
     * thousands/decimal separators while keeping Western digits, which is the
     * common convention in Moroccan Arabic interfaces.
     */
    function format_number(mixed $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (App::getLocale() === 'ar') {
            return number_format((float) $value, $decimals, '٫', '٬');
        }

        return number_format((float) $value, $decimals, ',', ' ');
    }
}

if (! function_exists('format_price')) {
    /**
     * Formate un montant avec la devise de la plateforme.
     *
     * Exemples :
     *   format_price(1500)           → "1 500,00 DH"
     *   format_price(1500, 'EUR')    → "1 500,00 EUR"
     *   format_price(null)           → "—"
     *
     * @param  float|int|string|null  $amount
     * @param  string|null  $currency  Si null → devise du config (MAD)
     */
    function format_price(mixed $amount, ?string $currency = null): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $symbol = $currency ?? config('currency.symbol', 'DH');
        $isMoroccanDirham = strtoupper((string) $symbol) === 'MAD'
            || ($currency === null && strtoupper(default_currency()) === 'MAD');
        // Si on passe le code ISO, afficher le symbol correspondant
        if ($isMoroccanDirham) {
            $symbol = App::getLocale() === 'ar' ? 'د.م.' : 'DH';
        }

        return format_number((float) $amount, 2).' '.$symbol;
    }
}

if (! function_exists('currency_symbol')) {
    /**
     * Retourne le symbole d'affichage de la devise courante.
     * MAD → "DH", tout autre code → le code lui-même.
     */
    function currency_symbol(?string $currency = null): string
    {
        $c = strtoupper($currency ?? config('currency.default', 'MAD'));
        if ($c === 'MAD') {
            return App::getLocale() === 'ar' ? 'د.م.' : 'DH';
        }

        return $c;
    }
}

if (! function_exists('default_currency')) {
    /**
     * Retourne le code ISO de la devise par défaut (MAD).
     */
    function default_currency(): string
    {
        return config('currency.default', 'MAD');
    }
}
