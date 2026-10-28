<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Domaine de formation affiché sur le portail Super Admin.
 *
 * Source de vérité des cartes de sélection des domaines : le portail lit cette
 * table au lieu d'afficher une liste codée en dur dans la vue.
 */
class Domain extends Model
{
    use HasTranslations;

    /**
     * Fields whose displayed value can be localized.
     *
     * @var list<string>
     */
    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'icon'       => 'home',
        'color'      => 'brand',
        'sort_order' => 0,
        'is_active'  => true,
    ];

    /**
     * Uniquement les domaines actifs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtrer par slug de domaine.
     */
    public function scopeSlug(Builder $query, ?string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Retrouver un domaine par son slug (null si inconnu).
     */
    public static function findBySlug(?string $slug): ?self
    {
        if (! $slug) {
            return null;
        }

        return static::where('slug', $slug)->first();
    }

    /**
     * Les formations rattachées à ce domaine (clé personnalisée domain <-> slug).
     */
    public function formations(): HasMany
    {
        return $this->hasMany(Formation::class, 'domain', 'slug');
    }

    /**
     * Ordre d'affichage croissant.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * URL du dashboard de ce domaine (route générique paramétrée).
     */
    public function routeUrl(): string
    {
        return route('super-admin.domain.dashboard', $this->slug);
    }
}
