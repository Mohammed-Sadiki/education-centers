<?php

namespace App\Support;

use App\Http\Controllers\SuperAdminDomainController;
use App\Models\Domain;
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
     * Domaines disponibles pour un centre.
     *
     * Les domaines constituent un catalogue COMMUN : chaque centre dispose de
     * tous les domaines actifs, indépendamment du fait qu'il possède déjà des
     * formations/étudiants dans un domaine. Le périmètre (données réelles) est
     * filtré par centre au niveau des dashboards et des listes, jamais au
     * niveau du catalogue — un domaine n'est donc jamais absent « faute de
     * données ». Méthode conservée pour la rétrocompatibilité d'appel.
     *
     * @return Collection<int, Domain>
     */
    public static function visibleForCenter(int $centerId): Collection
    {
        return static::visible();
    }

    /**
     * Le domaine est-il disponible pour ce centre ?
     *
     * Comme le catalogue est commun, tout domaine actif est disponible pour
     * tous les centres. L'isolation des données reste assurée par le forçage
     * du `center_id` côté dashboard/listes (un Admin Centre ne voit jamais les
     * données d'un autre centre), non par la restriction du catalogue.
     */
    public static function existsForCenter(string $slug, int $centerId): bool
    {
        return static::isValid($slug);
    }
}
