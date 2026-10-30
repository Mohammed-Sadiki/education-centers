<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Définitions partagées par les seeders de comptes de démonstration
 * (administrateurs et professeurs).
 *
 * Ces comptes sont volontairement isolés dans un espace de noms d'e-mails
 * réservé (« *.demo@education-centers.test ») afin de ne jamais entrer en
 * collision avec des comptes réels créés depuis l'interface. Ils sont
 * déterministes : la même exécution produit toujours les mêmes identifiants,
 * ce qui rend les seeders idempotents.
 */
final class DemoAccountFixtures
{
    /**
     * Domaine d'e-mail réservé aux comptes de démonstration.
     */
    public const DEMO_EMAIL_DOMAIN = 'education-centers.test';

    /**
     * Variable d'environnement (non versionnée) portant le mot de passe de démonstration.
     */
    public const PASSWORD_ENV = 'DEMO_DEFAULT_PASSWORD';

    /**
     * Valeur de repli si la variable d'environnement est absente.
     */
    public const PASSWORD_FALLBACK = 'DemoEducation2026!';

    /**
     * Les quatre centres marocains cibles, repérés par leur CODE (jamais par ID,
     * qui varie d'une machine à l'autre).
     *
     * @return list<array{code:string, city:string, label:string}>
     */
    public static function centers(): array
    {
        return [
            ['code' => 'TANGER',    'city' => 'tanger',    'label' => 'Tanger'],
            ['code' => 'AGADIR',    'city' => 'agadir',    'label' => 'Agadir'],
            ['code' => 'ESSAOUIRA', 'city' => 'essaouira', 'label' => 'Essaouira'],
            ['code' => 'TETOUANE',  'city' => 'tetouane',  'label' => 'Tétouane'],
        ];
    }

    public static function superAdminEmail(): string
    {
        return 'superadmin.demo@' . self::DEMO_EMAIL_DOMAIN;
    }

    public static function centerAdminEmail(string $city): string
    {
        return "admin.{$city}.demo@" . self::DEMO_EMAIL_DOMAIN;
    }

    public static function teacherEmail(string $city, string $domain): string
    {
        return "prof.demo.{$city}.{$domain}@" . self::DEMO_EMAIL_DOMAIN;
    }

    /**
     * Roster de professeurs de démonstration : un par domaine (six domaines),
     * dont deux affectés à un second domaine pour illustrer le multi-domaines.
     *
     * @return list<array<string, mixed>>
     */
    public static function teacherRoster(): array
    {
        return [
            [
                'domain' => 'sante',
                'secondary' => [],
                'first_name' => 'Amina',
                'last_name' => 'Bennani',
                'gender' => 'female',
            ],
            [
                'domain' => 'informatique',
                'secondary' => ['design-graphique'],
                'first_name' => 'Youssef',
                'last_name' => 'El Amrani',
                'gender' => 'male',
            ],
            [
                'domain' => 'hotellerie',
                'secondary' => [],
                'first_name' => 'Salma',
                'last_name' => 'Idrissi',
                'gender' => 'female',
            ],
            [
                'domain' => 'maconnerie',
                'secondary' => [],
                'first_name' => 'Rachid',
                'last_name' => 'Ouazzani',
                'gender' => 'male',
            ],
            [
                'domain' => 'design-interieur',
                'secondary' => ['design-graphique'],
                'first_name' => 'Khadija',
                'last_name' => 'Sabri',
                'gender' => 'female',
            ],
            [
                'domain' => 'design-graphique',
                'secondary' => [],
                'first_name' => 'Omar',
                'last_name' => 'Tazi',
                'gender' => 'male',
            ],
        ];
    }

    /**
     * Mot de passe de démonstration lu depuis l'environnement (jamais versionné).
     */
    public static function password(): string
    {
        return (string) env(self::PASSWORD_ENV, self::PASSWORD_FALLBACK);
    }

    /**
     * Garde-fou : les seeders de démonstration sont interdits en production.
     */
    public static function assertDemoEnvironmentAllowed(Seeder $seeder): void
    {
        if (app()->isProduction()) {
            $message = 'Les seeders de comptes de démonstration sont interdits en production.';

            $seeder->command?->error('⛔ ' . $message);

            throw new RuntimeException($message);
        }
    }
}
