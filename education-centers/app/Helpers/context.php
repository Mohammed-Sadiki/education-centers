<?php

declare(strict_types=1);

use App\Support\UserContext;

if (! function_exists('user_context')) {
    /**
     * Contexte applicatif courant (rôle + centre + domaine).
     *
     * Nomme user_context() et non context() car le framework reserve deja
     * context() (Illuminate\Log\Context\Repository).
     *
     * Résolu par le middleware ResolveDomainContext sur les requêtes HTTP.
     * Hors requête (console, tests unitaires) un contexte vide est renvoyé :
     * ni centre ni domaine restrictifs, donc aucun filtrage implicite.
     */
    function user_context(): UserContext
    {
        return app(UserContext::class);
    }
}
