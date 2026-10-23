<?php

namespace App\Http\Controllers;

use App\Support\UserContext;
use Illuminate\Database\Eloquent\Builder;

abstract class Controller
{
    /**
     * Restreindre une requête au domaine actif du contexte (aucun effet si
     * aucun domaine n'est sélectionné). Le modèle doit exposer `scopeForDomain`.
     */
    protected function applyDomainScope(Builder $query, ?UserContext $context = null): Builder
    {
        $context ??= user_context();

        if ($context->hasDomain() && method_exists($query->getModel(), 'scopeForDomain')) {
            $query->forDomain($context->domainSlug());
        }

        return $query;
    }

    /**
     * Restreindre une requête au centre + domaine du contexte courant.
     * Le volet centre n'est appliqué que si la table porte center_id et que
     * le contexte est restreint à un centre (Admin Centre, ou Super Admin
     * ayant volontairement filtré). Le modèle doit utiliser HasContextScope.
     */
    protected function applyContextScope(Builder $query, ?UserContext $context = null): Builder
    {
        $context ??= user_context();

        if (method_exists($query->getModel(), 'scopeWithinContext')) {
            $query->withinContext($context);
        }

        return $query;
    }
}
