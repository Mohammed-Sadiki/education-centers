<?php

namespace App\Models\Concerns;

use App\Support\UserContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtrage « dans le contexte courant » (centre + domaine).
 *
 * Contrairement à un global scope, ce filtrage est explicite et opt-in : les
 * contrôleurs qui doivent respecter le périmètre appelent `withinContext()`.
 * Il s'appuie sur :
 *  - la colonne `center_id` de la table pour le volet centre ;
 *  - le trait {@see \App\Concerns\HasDomainScope} (scope `forDomain`) pour le
 *    volet domaine, lorsque le modèle le déclare.
 *
 * Super Admin sans centre restreint et sans domaine actif => aucun filtre
 * (vue globale), ce qui préserve le comportement existant.
 */
trait HasContextScope
{
    /**
     * Restreindre la requête au centre et au domaine du contexte courant.
     */
    public function scopeWithinContext(Builder $query, ?UserContext $context = null): Builder
    {
        $context ??= app(UserContext::class);

        // Volet centre : uniquement si la table porte center_id.
        if ($context->hasCenter() && $this->hasCenterColumn()) {
            $query->where($this->qualifyColumn('center_id'), $context->centerId());
        }

        // Volet domaine : si le modèle sait se filtrer par domaine et qu'un domaine est actif.
        if ($context->hasDomain() && method_exists($this, 'scopeForDomain')) {
            $query->forDomain($context->domainSlug());
        }

        return $query;
    }

    /**
     * Seulement le volet centre du contexte.
     */
    public function scopeWithinCenterContext(Builder $query, ?UserContext $context = null): Builder
    {
        $context ??= app(UserContext::class);

        if ($context->hasCenter() && $this->hasCenterColumn()) {
            $query->where($this->qualifyColumn('center_id'), $context->centerId());
        }

        return $query;
    }

    /**
     * La table possède-t-elle une colonne center_id ?
     */
    protected function hasCenterColumn(): bool
    {
        return array_key_exists('center_id', $this->getAttributes())
            || in_array('center_id', $this->getFillable(), true);
    }
}
