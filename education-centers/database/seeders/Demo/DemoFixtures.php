<?php

namespace Database\Seeders\Demo;

use App\Models\Center;

/**
 * Fabrique de données déterministes pour la démonstration.
 *
 * Toutes les valeurs sont stables d'une exécution à l'autre afin que les
 * seeders puissent être rejoués sans créer de doublons.
 */
final class DemoFixtures
{
    public const SCHOOL_YEAR = '2026/2027';

    public const DEMO_PASSWORD_ENV = 'DEMO_DEFAULT_PASSWORD';

    /**
     * @return list<string>
     */
    public static function domainSlugs(): array
    {
        return [
            'sante',
            'informatique',
            'hotellerie',
            'maconnerie',
            'design-interieur',
            'design-graphique',
        ];
    }

    public static function domainLabel(string $domain): string
    {
        return [
            'sante' => 'Santé',
            'informatique' => 'Informatique',
            'hotellerie' => 'Hôtellerie',
            'maconnerie' => 'Maçonnerie',
            'design-interieur' => 'Design d’intérieur',
            'design-graphique' => 'Design graphique',
        ][$domain] ?? $domain;
    }

    public static function centerCode(Center $center): string
    {
        return $center->code === 'BERLIN' ? 'BER' : 'MUN';
    }

    public static function centerLabel(Center $center): string
    {
        return $center->code === 'BERLIN' ? 'Berlin' : 'Munich';
    }

    /**
     * Douze formations par centre : deux par domaine.
     *
     * L'ordre est volontairement stable : il sert aussi à ordonner les classes,
     * les professeurs et les séances.
     *
     * @return list<array<string, mixed>>
     */
    public static function formationBlueprints(Center $center): array
    {
        $cc = self::centerCode($center);

        return [
            [
                'code' => "DEMO-{$cc}-SAN-SOINS",
                'domain' => 'sante',
                'name' => 'Soins infirmiers & Aide-Soignant',
                'description' => 'Formation professionnelle aux soins de base, à l’hygiène hospitalière et à l’accompagnement des patients.',
                'duration_hours' => 1200,
                'duration_weeks' => 48,
                'annual_price' => 15000.00,
            ],
            [
                'code' => "DEMO-{$cc}-SAN-SECRET",
                'domain' => 'sante',
                'name' => 'Secrétariat Médical & Assistance Administrative',
                'description' => 'Gestion du dossier patient, terminologie médicale, accueil et organisation d’un cabinet ou d’un service hospitalier.',
                'duration_hours' => 900,
                'duration_weeks' => 36,
                'annual_price' => 14500.00,
            ],
            [
                'code' => "DEMO-{$cc}-INFO-WEB",
                'domain' => 'informatique',
                'name' => 'Développement Web Full Stack',
                'description' => 'HTML, CSS, JavaScript, PHP, bases de données et déploiement d’applications web modernes.',
                'duration_hours' => 1100,
                'duration_weeks' => 44,
                'annual_price' => 14000.00,
            ],
            [
                'code' => "DEMO-{$cc}-INFO-CYBER",
                'domain' => 'informatique',
                'name' => 'Cybersécurité & Administration Réseaux',
                'description' => 'Sécurisation des systèmes, réseaux TCP/IP, supervision, prévention des incidents et bonnes pratiques.',
                'duration_hours' => 1050,
                'duration_weeks' => 42,
                'annual_price' => 14800.00,
            ],
            [
                'code' => "DEMO-{$cc}-HOT-CUISINE",
                'domain' => 'hotellerie',
                'name' => 'Arts Culinaires & Gestion de Cuisine',
                'description' => 'Techniques culinaires professionnelles, hygiène HACCP, coût matière et organisation de brigade.',
                'duration_hours' => 1000,
                'duration_weeks' => 40,
                'annual_price' => 12000.00,
            ],
            [
                'code' => "DEMO-{$cc}-HOT-SERVICE",
                'domain' => 'hotellerie',
                'name' => 'Service en Salle & Gestion Hôtelière',
                'description' => 'Accueil, service en salle, œnologie, relation client et gestion opérationnelle d’un établissement.',
                'duration_hours' => 850,
                'duration_weeks' => 34,
                'annual_price' => 11500.00,
            ],
            [
                'code' => "DEMO-{$cc}-MAC-GROS",
                'domain' => 'maconnerie',
                'name' => 'Gros Œuvre & Maçonnerie Générale',
                'description' => 'Lecture de plans, implantation, coffrage, béton armé et sécurité sur chantier.',
                'duration_hours' => 1050,
                'duration_weeks' => 42,
                'annual_price' => 10000.00,
            ],
            [
                'code' => "DEMO-{$cc}-MAC-RENOV",
                'domain' => 'maconnerie',
                'name' => 'Rénovation & Conduite de Chantier',
                'description' => 'Réhabilitation, isolation, finitions et coordination des corps de métier sur un chantier.',
                'duration_hours' => 950,
                'duration_weeks' => 38,
                'annual_price' => 10500.00,
            ],
            [
                'code' => "DEMO-{$cc}-DI-ESPACE",
                'domain' => 'design-interieur',
                'name' => 'Aménagement d’Espace & Décoration',
                'description' => 'Conception d’espaces, couleurs, matériaux, mobilier et présentation de projets d’aménagement.',
                'duration_hours' => 900,
                'duration_weeks' => 36,
                'annual_price' => 13000.00,
            ],
            [
                'code' => "DEMO-{$cc}-DI-3D",
                'domain' => 'design-interieur',
                'name' => 'Modélisation 3D & Scénographie',
                'description' => 'Maquettes numériques, rendus photoréalistes, lumière et scénographie d’espaces intérieurs.',
                'duration_hours' => 880,
                'duration_weeks' => 34,
                'annual_price' => 13500.00,
            ],
            [
                'code' => "DEMO-{$cc}-DG-BRAND",
                'domain' => 'design-graphique',
                'name' => 'Identité Visuelle & Branding',
                'description' => 'Création de logos, chartes graphiques, direction artistique et déclinaison de marque.',
                'duration_hours' => 860,
                'duration_weeks' => 34,
                'annual_price' => 12000.00,
            ],
            [
                'code' => "DEMO-{$cc}-DG-UX",
                'domain' => 'design-graphique',
                'name' => 'UI/UX Design & Illustration',
                'description' => 'Parcours utilisateur, wireframes, prototypage, design d’interface et illustration numérique.',
                'duration_hours' => 900,
                'duration_weeks' => 36,
                'annual_price' => 12500.00,
            ],
        ];
    }

    /**
     * Anciens codes créés par le seeder initial, réutilisés sans suppression.
     *
     * @return array<string, string> legacy code => nouveau code
     */
    public static function legacyFormationCodes(Center $center): array
    {
        if ($center->code === 'BERLIN') {
            return [
                'DE-A1' => 'DEMO-BER-INFO-WEB',
                'DE-B1' => 'DEMO-BER-INFO-CYBER',
                'IT-BASIC' => 'DEMO-BER-SAN-SOINS',
            ];
        }

        return [
            'DE-A1' => 'DEMO-MUN-SAN-SOINS',
            'EN-B2' => 'DEMO-MUN-SAN-SECRET',
        ];
    }

    /**
     * Douze professeurs par centre, un par classe, deux par domaine.
     *
     * @return list<array<string, mixed>>
     */
    public static function teacherBlueprints(Center $center): array
    {
        $domains = [
            'sante', 'sante', 'informatique', 'informatique',
            'hotellerie', 'hotellerie', 'maconnerie', 'maconnerie',
            'design-interieur', 'design-interieur', 'design-graphique', 'design-graphique',
        ];

        $names = [
            ['Lena', 'Hoffmann'], ['Karim', 'Benali'], ['Johannes', 'Kohl'], ['Sofia', 'Weber'],
            ['Greta', 'Schneider'], ['Youssef', 'Amrani'], ['Clara', 'Fischer'], ['David', 'Meyer'],
            ['Nora', 'Klein'], ['Mehmet', 'Yilmaz'], ['Amelie', 'Schulz'], ['Tobias', 'Wagner'],
        ];

        if ($center->code === 'MUNICH') {
            $names = [
                ['Greta', 'Schneider'], ['Karim', 'Benali'], ['Lena', 'Hoffmann'], ['Sofia', 'Weber'],
                ['Johannes', 'Kohl'], ['Youssef', 'Amrani'], ['Clara', 'Fischer'], ['David', 'Meyer'],
                ['Nora', 'Klein'], ['Mehmet', 'Yilmaz'], ['Amelie', 'Schulz'], ['Tobias', 'Wagner'],
            ];
        }

        $legacyIndex = $center->code === 'BERLIN' ? 2 : 0;
        $legacyNumber = $center->code === 'BERLIN' ? 'BER-T-000001' : 'MUN-T-000001';
        $legacyEmail = $center->code === 'BERLIN'
            ? 'prof.berlin@education-centers.de'
            : 'prof.munich@education-centers.de';

        $counter = 1;
        $definitions = [];

        foreach ($domains as $index => $domain) {
            $isLegacy = $index === $legacyIndex;
            $number = $isLegacy
                ? $legacyNumber
                : sprintf('%s-T-DEMO-%02d', self::centerCode($center), $counter++);
            $email = $isLegacy
                ? $legacyEmail
                : sprintf('prof.demo.%s.%02d@education-centers.test', strtolower(self::centerLabel($center)), $counter - 1);

            $monthlySalary = match ($domain) {
                'sante' => 7200.00,
                'informatique' => 7600.00,
                'hotellerie' => 6400.00,
                'maconnerie' => 6200.00,
                'design-interieur' => 6800.00,
                default => 6600.00,
            };

            if ($center->code === 'BERLIN' && $index === 2) {
                $monthlySalary = 7500.00;
            }
            if ($center->code === 'MUNICH' && $index === 0) {
                $monthlySalary = 8500.00;
            }

            $definitions[] = [
                'teacher_number' => $number,
                'domain' => $domain,
                'first_name' => $names[$index][0],
                'last_name' => $names[$index][1],
                'email' => $email,
                'monthly_salary' => $monthlySalary,
                'specialization' => self::domainSpecialization($domain),
                'qualification' => self::domainQualification($domain),
                'is_legacy' => $isLegacy,
            ];
        }

        return $definitions;
    }

    /**
     * Douze classes par centre, une par formation.
     *
     * @return list<array<string, mixed>>
     */
    public static function classBlueprints(Center $center): array
    {
        $formations = self::formationBlueprints($center);
        $classes = [];

        foreach ($formations as $formation) {
            $suffix = str_replace('DEMO-'.self::centerCode($center).'-', '', $formation['code']);
            $classes[] = [
                'code' => 'DEMO-'.self::centerCode($center)."-{$suffix}-A",
                'formation_code' => $formation['code'],
                'domain' => $formation['domain'],
                'name' => $formation['name'].' · Groupe A',
                'description' => 'Groupe de démonstration 2026/2027 pour la formation '.$formation['name'].'.',
                'capacity' => 30,
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
            ];
        }

        return $classes;
    }

    /**
     * @return array<string, string> ancien code => nouveau code
     */
    public static function legacyClassCodes(Center $center): array
    {
        if ($center->code === 'BERLIN') {
            return [
                'A1-01' => 'DEMO-BER-INFO-WEB-A',
                'A1-02' => 'DEMO-BER-SAN-SOINS-A',
                'B1-01' => 'DEMO-BER-INFO-CYBER-A',
            ];
        }

        return [
            'A1-01' => 'DEMO-MUN-SAN-SOINS-A',
            'B2-01' => 'DEMO-MUN-SAN-SECRET-A',
        ];
    }

    /**
     * Soixante profils étudiants fictifs par centre. Le seeder ne crée que le
     * nombre manquant pour atteindre exactement 60 étudiants dans le centre.
     *
     * @return list<array<string, mixed>>
     */
    public static function studentBlueprints(Center $center): array
    {
        $firstNames = [
            'Lena', 'Jonas', 'Amira', 'Noah', 'Sofia', 'Karim', 'Emma', 'Youssef', 'Clara', 'David',
            'Maya', 'Elias', 'Nora', 'Mehmet', 'Julie', 'Rayan', 'Anna', 'Tobias', 'Sara', 'Paul',
            'Inès', 'Bilal', 'Lea', 'Samuel', 'Aya', 'Felix', 'Mina', 'Omar', 'Laura', 'Nils',
            'Rania', 'Tom', 'Selma', 'Lucas', 'Hiba', 'Adam', 'Chloe', 'Yassine', 'Nina', 'Erik',
            'Kenza', 'Max', 'Alina', 'Ilyes', 'Ida', 'Nicolas', 'Fatima', 'Julian', 'Lina', 'Bastien',
            'Amina', 'Milan', 'Eva', 'Sami', 'Marta', 'Hugo', 'Soraya', 'Anton', 'Malak', 'Leon',
        ];

        $lastNames = [
            'Hoffmann', 'Benali', 'Weber', 'Schmidt', 'Amrani', 'Fischer', 'Meyer', 'Klein', 'Yilmaz', 'Schulz',
            'Wagner', 'Becker', 'Koch', 'Richter', 'Braun', 'Krüger', 'Neumann', 'Schwarz', 'Zimmermann', 'Wolf',
            'Hartmann', 'Lange', 'Werner', 'Krause', 'Meier', 'Lehmann', 'Köhler', 'Herrmann', 'Walter', 'Mayer',
            'Huber', 'Kaiser', 'Fuchs', 'Peters', 'Lang', 'Scholz', 'Möller', 'Weiß', 'Jung', 'Hahn',
            'Vogel', 'Friedrich', 'Keller', 'Günther', 'Frank', 'Berger', 'Winkler', 'Roth', 'Beck', 'Lorenz',
            'Baumann', 'Franke', 'Albrecht', 'Schuster', 'Simon', 'Ludwig', 'Böhm', 'Winter', 'Kraus', 'Martin',
        ];

        $streets = [
            'Lindenstraße', 'Hauptstraße', 'Bahnhofstraße', 'Gartenweg', 'Schulstraße',
            'Ringstraße', 'Am Markt', 'Friedrichstraße', 'Mozartstraße', 'Goethestraße',
        ];

        $centerLabel = self::centerLabel($center);
        $cc = self::centerCode($center);
        $city = $center->code === 'BERLIN' ? 'Berlin' : 'Munich';
        $postal = $center->code === 'BERLIN' ? '10115' : '80331';
        $offset = $center->code === 'BERLIN' ? 0 : 13;
        $blueprints = [];

        for ($i = 0; $i < 60; $i++) {
            $firstName = $firstNames[$i];
            $lastName = $lastNames[($i + $offset) % count($lastNames)];
            $number = sprintf('%s-DEMO-%04d', $cc, $i + 1);
            $gender = in_array($firstName, ['Lena', 'Amira', 'Sofia', 'Emma', 'Clara', 'Maya', 'Nora', 'Julie', 'Anna', 'Sara', 'Inès', 'Lea', 'Aya', 'Mina', 'Laura', 'Rania', 'Selma', 'Hiba', 'Chloe', 'Nina', 'Kenza', 'Alina', 'Ida', 'Fatima', 'Lina', 'Amina', 'Eva', 'Marta', 'Soraya', 'Malak'], true)
                ? 'female'
                : 'male';

            $dob = date('Y-m-d', strtotime('2004-01-15') + ($i * 47) * 86400);

            $blueprints[] = [
                'student_number' => $number,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => sprintf('demo.%s.%04d@education-centers.test', strtolower($centerLabel), $i + 1),
                'date_of_birth' => $dob,
                'place_of_birth' => $city,
                'gender' => $gender,
                'nationality' => $i % 4 === 0 ? 'Française' : ($i % 4 === 1 ? 'Allemande' : ($i % 4 === 2 ? 'Marocaine' : 'Turque')),
                'address' => ($streets[$i % count($streets)]).' '.($i + 12),
                'postal_code' => $postal,
                'city' => $city,
                'country' => 'Germany',
                'phone' => sprintf('+49 %s %08d', $center->code === 'BERLIN' ? '30' : '89', 10000000 + ($i * 137)),
                'emergency_contact_name' => $lastNames[($i + 5 + $offset) % count($lastNames)],
                'emergency_contact_phone' => sprintf('+49 17%d %08d', $i % 10, 20000000 + ($i * 271)),
                'emergency_contact_relation' => $i % 3 === 0 ? 'Parent' : ($i % 3 === 1 ? 'Conjoint(e)' : 'Tuteur'),
                'notes' => 'Dossier de démonstration fictif – aucune donnée réelle.',
                'status' => 'active',
            ];
        }

        return $blueprints;
    }

    public static function domainSpecialization(string $domain): string
    {
        return [
            'sante' => 'Soins infirmiers et accompagnement patient',
            'informatique' => 'Développement web et systèmes d’information',
            'hotellerie' => 'Cuisine, service et management hôtelier',
            'maconnerie' => 'Gros œuvre et conduite de chantier',
            'design-interieur' => 'Architecture d’intérieur et scénographie',
            'design-graphique' => 'Direction artistique et design numérique',
        ][$domain] ?? 'Formation professionnelle';
    }

    public static function domainQualification(string $domain): string
    {
        return [
            'sante' => 'Master en sciences infirmières',
            'informatique' => 'Master en génie logiciel',
            'hotellerie' => 'Diplôme supérieur de management hôtelier',
            'maconnerie' => 'Brevet professionnel de maçonnerie',
            'design-interieur' => 'Diplôme supérieur en architecture d’intérieur',
            'design-graphique' => 'Master en design graphique',
        ][$domain] ?? 'Diplôme professionnel';
    }
}
