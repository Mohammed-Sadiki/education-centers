<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Filtrage par domaine de formation, en modèle "relation-dérivé".
 *
 * Le domaine est porté par la colonne string `domain` (slug) de la table
 * `formations`. Les entités liées héritent du domaine via leur(s) relation(s)
 * vers une formation, sans dupliquer de colonne `domain_id` partout.
 *
 * Chaque modèle déclare le ou les chemins de relation vers la (les) formation(s)
 * qui portent son domaine via domainPaths(). Un tableau vide signifie que la
 * table porte elle-même sa colonne `domain` (cas de Formation).
 */
trait HasDomainScope
{
    /**
     * Chemin(s) de relation vers une formation dont la colonne `domain` définit
     * le domaine de cet enregistrement. Vide => colonne `domain` propre à la table.
     *
     * @return list<string>
     */
    protected function domainPaths(): array
    {
        return [];
    }

    /**
     * Restreindre une requête à un domaine (slug). `null` ou `'all'` = aucun filtre.
     */
    public function scopeForDomain(Builder $query, ?string $slug): Builder
    {
        if (! $slug || $slug === 'all') {
            return $query;
        }

        $paths = $this->domainPaths();

        if ($paths === []) {
            // Domaine porté directement par cette table (ex. formations.domain).
            return $query->where($this->qualifyColumn('domain'), $slug);
        }

        return $query->where(function (Builder $q) use ($paths, $slug) {
            foreach ($paths as $path) {
                $q->orWhereHas($path, fn (Builder $f) => $f->where('domain', $slug));
            }
        });
    }

    /**
     * Slug du domaine de cet enregistrement (null si non déterminable, ex.
     * étudiant/professeur multi-domaines ou relation non chargée en collection).
     */
    public function domainSlug(): ?string
    {
        $paths = $this->domainPaths();

        if ($paths === []) {
            return $this->getAttribute('domain');
        }

        $node = $this;
        foreach (explode('.', $paths[0]) as $segment) {
            $node = $node?->{$segment};

            if ($node === null || $node instanceof Collection) {
                return null;
            }
        }

        return $node->getAttribute('domain');
    }
}
