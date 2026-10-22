<?php

declare(strict_types=1);

if (! function_exists('translate_label')) {
    /**
     * Translate a label persisted in the database.
     *
     * Finance labels are written once in French ("Dépense : ...",
     * "Contrepassation annulation : ...") and must stay untouched in the
     * database. This helper translates known prefixes and known dictionary
     * terms at display time, while preserving the dynamic remainder.
     */
    function translate_label(?string $label, ?string $locale = null): string
    {
        if ($label === null || trim($label) === '') {
            return '—';
        }

        if ($locale !== null) {
            app()->setLocale($locale);
        }

        $prefixes = [
            'Contrepassation annulation : ' => __('Contrepassation annulation').' : ',
            'Contrepassation annulation:' => __('Contrepassation annulation').':',
            'Dépense : ' => __('Dépense').' : ',
            'Dépense :' => __('Dépense').':',
            'Recette : ' => __('Recette').' : ',
            'Paiement : ' => __('Paiement').' : ',
            'Salaire : ' => __('Salaire').' : ',
        ];

        foreach ($prefixes as $french => $translated) {
            if (str_starts_with($label, $french)) {
                $remainder = substr($label, strlen($french));

                return $translated.translate_label($remainder);
            }
        }

        // Dictionary lookup for exact stored terms; unknown values fall through.
        return (string) __($label);
    }
}
