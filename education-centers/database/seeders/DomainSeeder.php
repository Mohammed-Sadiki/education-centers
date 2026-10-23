<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

/**
 * Alimente la table des domaines de formation du portail Super Admin.
 *
 * Les libellés et descriptions sont stockés en français ; la traduction arabe /
 * anglaise est assurée par les fichiers JSON de traduction (les chaînes servent
 * de clés dans lang/*.json).
 */
class DomainSeeder extends Seeder
{
    /**
     * Définition canonique des domaines (source unique).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function domains(): array
    {
        return [
            [
                'slug'        => 'sante',
                'name'        => 'Santé',
                'description' => 'Formations paramédicales, soins infirmiers, aides-soignants, secrétariat médical et professions de santé.',
                'icon'        => 'heart',
                'color'       => 'emerald',
                'sort_order'  => 1,
            ],
            [
                'slug'        => 'informatique',
                'name'        => 'Informatique',
                'description' => 'Développement web, cybersécurité, réseaux, administration systèmes et intelligence artificielle.',
                'icon'        => 'monitor',
                'color'       => 'blue',
                'sort_order'  => 2,
            ],
            [
                'slug'        => 'hotellerie',
                'name'        => 'Hôtellerie',
                'description' => 'Arts culinaires, service en salle, gestion hôtelière, hébergement et tourisme.',
                'icon'        => 'building',
                'color'       => 'amber',
                'sort_order'  => 3,
            ],
            [
                'slug'        => 'maconnerie',
                'name'        => 'Maçonnerie',
                'description' => 'Gros œuvre, maçonnerie générale, rénovation, béton armé et conduite de chantiers du BTP.',
                'icon'        => 'wrench',
                'color'       => 'orange',
                'sort_order'  => 4,
            ],
            [
                'slug'        => 'design-interieur',
                'name'        => 'Design intérieur',
                'description' => "Aménagement d'espace, décoration, modélisation 3D architecturale et scénographie.",
                'icon'        => 'home',
                'color'       => 'violet',
                'sort_order'  => 5,
            ],
            [
                'slug'        => 'design-graphique',
                'name'        => 'Design graphique',
                'description' => 'Identité visuelle, typographie, illustration vectorielle, UI/UX design et packaging.',
                'icon'        => 'brush',
                'color'       => 'pink',
                'sort_order'  => 6,
            ],
        ];
    }

    /**
     * Insère ou met à jour les domaines de démonstration.
     */
    public function run(): void
    {
        foreach (self::domains() as $domainData) {
            Domain::updateOrCreate(
                ['slug' => $domainData['slug']],
                $domainData + ['is_active' => true]
            );
        }

        $this->command?->info('✅ Domaines de formation chargés : ' . count(self::domains()) . '.');
    }
}
