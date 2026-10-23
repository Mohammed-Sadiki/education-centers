<?php

namespace App\Support;

use App\Http\Controllers\SuperAdminDomainController;
use App\Models\Domain;
use App\Models\Formation;
use Illuminate\Support\Collection;

/**
 * Source de vérité unique des domaines de formation.
 *
 * Priorité à la table `domains` (pilotée en base). Quand la table est vide
 * (base non encore semée, ou suite de tests `RefreshDatabase`), on retombe sur
 * la liste canonique du contrôleur Super Admin sans écriture en base. Cela
 * garantit que la validation et l'affichage fonctionnent dans les deux cas.
 */
class DomainRegistry
{
    /**
     * Libellés canoniques slug => label (repli quand la table est vide).
     *
     * @return array<string, string>
     */
    public static function canonical(): array
    {
        return SuperAdminDomainController::DOMAINS;
    }

    /**
     * La table `domains` contient-elle des domaines ?
     */
    public static function hasDatabaseRows(): bool
    {
        return Domain::query()->exists();
    }

    /**
     * Tous les slugs de domaine connus (base si peuplée, sinon canonique).
     *
     * @return list<string>
     */
    public static function slugs(): array
    {
        if (static::hasDatabaseRows()) {
            return Domain::query()->ordered()->pluck('slug')->all();
        }

        return array_keys(static::canonical());
    }

    /**
     * Slug => label, pour l'affichage et les filtres.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        if (static::hasDatabaseRows()) {
            return Domain::query()->ordered()->pluck('name', 'slug')->all();
        }

        return static::canonical();
    }

    /**
     * Un slug est-il valide (présent en base, ou dans le repli canonique).
     * `null` et `all` ne sont pas des domaines valides (contexte « tous »).
     */
    public static function isValid(?string $slug): bool
    {
        if (! $slug || $slug === 'all') {
            return false;
        }

        if (static::hasDatabaseRows()) {
            return Domain::query()->where('slug', $slug)->active()->exists();
        }

        return array_key_exists($slug, static::canonical());
    }

    /**
     * Retrouve le modèle Domain d'un slug (hydraté depuis le repli si besoin).
     */
    public static function find(?string $slug): ?Domain
    {
        if (! $slug || $slug === 'all') {
            return null;
        }

        $found = Domain::findBySlug($slug);
        if ($found) {
            return $found;
        }

        // Repli canonique : domaine connu mais absent de la table (base vide).
        if (! static::hasDatabaseRows() && ($label = static::canonical()[$slug] ?? null)) {
            return new Domain([
                'slug'      => $slug,
                'name'      => $label,
                'is_active' => true,
            ]);
        }

        return null;
    }

    /**
     * Collection de domaines à afficher sur le portail (actifs, ordonnés).
     *
     * @return Collection<int, Domain>
     */
    public static function visible(): Collection
    {
        if (static::hasDatabaseRows()) {
            return Domain::query()->active()->ordered()->get();
        }

        return collect(static::canonical())
            ->map(fn (string $label, string $slug): Domain => new Domain([
                'slug'      => $slug,
                'name'      => $label,
                'is_active' => true,
            ]))
            ->values();
    }

    /**
     * Domaines effectivement proposés par un centre : les domaines des
     * formations rattachées à ce centre. Si le centre n'a aucune formation
     * (base de test vide, centre fraîchement créé), on retombe sur la liste
     * visible complète pour ne jamais bloquer la navigation.
     *
     * @return Collection<int, Domain>
     */
    public static function visibleForCenter(int $centerId): Collection
    {
        $slugs = Formation::query()
            ->where('center_id', $centerId)
            ->whereNotNull('domain')
            ->distinct()
            ->pluck('domain')
            ->all();

        if ($slugs === []) {
            return static::visible();
        }

        $bySlug = static::visible()->keyBy('slug');

        return collect($slugs)
            ->map(fn (string $slug): ?Domain => $bySlug->get($slug))
            ->filter()
            ->values();
    }

    /**
     * Le domaine est-il proposé par ce centre ? (isolation stricte Admin Centre)
     */
    public static function existsForCenter(string $slug, int $centerId): bool
    {
        $slugs = Formation::query()
            ->where('center_id', $centerId)
            ->whereNotNull('domain')
            ->distinct()
            ->pluck('domain')
            ->all();

        // Aucun formation du centre => on s'en remet à la validité globale.
        if ($slugs === []) {
            return static::isValid($slug);
        }

        return in_array($slug, $slugs, true);
    }
}
