<?php

declare(strict_types=1);

use function Illuminate\Support\Str;

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
     * @param  string|null            $currency  Si null → devise du config (MAD)
     */
    function format_price(mixed $amount, ?string $currency = null): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $symbol   = $currency ?? config('currency.symbol', 'DH');
        // Si on passe le code ISO, afficher le symbol correspondant
        if (strtoupper((string) $symbol) === 'MAD') {
            $symbol = 'DH';
        }

        return number_format((float) $amount, 2, ',', ' ') . ' ' . $symbol;
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
        return $c === 'MAD' ? 'DH' : $c;
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
