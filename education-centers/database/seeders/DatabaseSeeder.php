<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Ordre important :
     * 1. Centers d'abord (les users y feront référence via center_id)
     * 2. Users ensuite
     */
    public function run(): void
    {
        // 1. Créer les centres de démonstration
        $this->call(CenterSeeder::class);

        // 2. Créer un utilisateur Super Admin de démonstration (role = super_admin, center_id = null)
        $devPassword = config('app.dev_admin_password', 'EducationAdmin2026!');

        User::updateOrCreate(
            ['email' => 'admin@education-centers.de'],
            [
                'name'      => 'Super Admin',
                'password'  => \Illuminate\Support\Facades\Hash::make($devPassword),
                'role'      => User::ROLE_SUPER_ADMIN,
                'center_id' => null, // Super Admin : accès à tous les centres
            ]
        );

        // 3. Récupérer les centres Berlin et Munich
        $berlinCenter = \App\Models\Center::where('code', 'BERLIN')->first();
        $munichCenter = \App\Models\Center::where('code', 'MUNICH')->first();

        // 4. Admin Centre Berlin
        if ($berlinCenter) {
            $berlinUser = User::firstOrNew(['email' => 'admin.berlin@education-centers.de']);
            $berlinUser->name = 'Admin Centre Berlin';
            $berlinUser->password = \Illuminate\Support\Facades\Hash::make($devPassword);
            $berlinUser->role = User::ROLE_CENTER_ADMIN;
            $berlinUser->center_id = $berlinCenter->id;
            $berlinUser->save();
        }

        // 5. Admin Centre Munich
        if ($munichCenter) {
            $munichUser = User::firstOrNew(['email' => 'admin.munich@education-centers.de']);
            $munichUser->name = 'Admin Centre Munich';
            $munichUser->password = \Illuminate\Support\Facades\Hash::make($devPassword);
            $munichUser->role = User::ROLE_CENTER_ADMIN;
            $munichUser->center_id = $munichCenter->id;
            $munichUser->save();
        }

        $this->command->info('✅ Comptes de démonstration créés : Super Admin, Admin Berlin, Admin Munich.');

        // 6. Professeur Berlin
        if ($berlinCenter) {
            $teacherBerlin = User::firstOrNew(['email' => 'prof.berlin@education-centers.de']);
            $teacherBerlin->name = 'Professeur Berlin';
            $teacherBerlin->password = \Illuminate\Support\Facades\Hash::make($devPassword);
            $teacherBerlin->role = User::ROLE_TEACHER;
            $teacherBerlin->center_id = $berlinCenter->id;
            $teacherBerlin->status = 'active';
            $teacherBerlin->save();
        }

        // 7. Professeur Munich
        if ($munichCenter) {
            $teacherMunich = User::firstOrNew(['email' => 'prof.munich@education-centers.de']);
            $teacherMunich->name = 'Professeur Munich';
            $teacherMunich->password = \Illuminate\Support\Facades\Hash::make($devPassword);
            $teacherMunich->role = User::ROLE_TEACHER;
            $teacherMunich->center_id = $munichCenter->id;
            $teacherMunich->status = 'active';
            $teacherMunich->save();
        }

        // 8. Élève Berlin
        if ($berlinCenter) {
            $studentBerlin = User::firstOrNew(['email' => 'eleve.berlin@education-centers.de']);
            $studentBerlin->name = 'Élève Berlin';
            $studentBerlin->password = \Illuminate\Support\Facades\Hash::make($devPassword);
            $studentBerlin->role = User::ROLE_STUDENT;
            $studentBerlin->center_id = $berlinCenter->id;
            $studentBerlin->status = 'active';
            $studentBerlin->save();
        }

        // 9. Élève Munich
        if ($munichCenter) {
            $studentMunich = User::firstOrNew(['email' => 'eleve.munich@education-centers.de']);
            $studentMunich->name = 'Élève Munich';
            $studentMunich->password = \Illuminate\Support\Facades\Hash::make($devPassword);
            $studentMunich->role = User::ROLE_STUDENT;
            $studentMunich->center_id = $munichCenter->id;
            $studentMunich->status = 'active';
            $studentMunich->save();
        }

        // 10. Dossiers étudiants pour Berlin et Munich
        if ($berlinCenter && isset($studentBerlin)) {
            \App\Models\Student::updateOrCreate(
                ['user_id' => $studentBerlin->id],
                [
                    'center_id'                  => $berlinCenter->id,
                    'student_number'             => 'BER-000001',
                    'first_name'                 => 'Hiba',
                    'last_name'                  => 'Berlin',
                    'date_of_birth'              => '2002-05-14',
                    'place_of_birth'             => 'Berlin',
                    'gender'                     => 'female',
                    'nationality'                => 'Allemande',
                    'address'                    => 'Friedrichstraße 42',
                    'postal_code'                => '10117',
                    'city'                       => 'Berlin',
                    'country'                    => 'Germany',
                    'phone'                      => '+49 30 11223344',
                    'emergency_contact_name'     => 'Klaus Schmidt',
                    'emergency_contact_phone'    => '+49 170 1234567',
                    'emergency_contact_relation' => 'Parent',
                    'notes'                      => 'Dossier complet. Inscription validée.',
                    'status'                     => 'active',
                ]
            );
        }

        if ($munichCenter && isset($studentMunich)) {
            \App\Models\Student::updateOrCreate(
                ['user_id' => $studentMunich->id],
                [
                    'center_id'                  => $munichCenter->id,
                    'student_number'             => 'MUN-000001',
                    'first_name'                 => 'Maximilian',
                    'last_name'                  => 'Munich',
                    'date_of_birth'              => '2001-09-21',
                    'place_of_birth'             => 'München',
                    'gender'                     => 'male',
                    'nationality'                => 'Allemande',
                    'address'                    => 'Maximilianstraße 15',
                    'postal_code'                => '80539',
                    'city'                       => 'München',
                    'country'                    => 'Germany',
                    'phone'                      => '+49 89 99887766',
                    'emergency_contact_name'     => 'Anna Weber',
                    'emergency_contact_phone'    => '+49 171 7654321',
                    'emergency_contact_relation' => 'Mère',
                    'notes'                      => 'Dossier administratif en règle.',
                    'status'                     => 'active',
                ]
            );
        }

        // 11. Dossiers professeurs pour Berlin et Munich
        if ($berlinCenter && isset($teacherBerlin)) {
            \App\Models\Teacher::updateOrCreate(
                ['user_id' => $teacherBerlin->id],
                [
                    'center_id'      => $berlinCenter->id,
                    'teacher_number' => 'BER-T-000001',
                    'first_name'     => 'Johannes',
                    'last_name'      => 'Kohl',
                    'date_of_birth'  => '1985-03-12',
                    'place_of_birth' => 'Berlin',
                    'gender'         => 'male',
                    'nationality'    => 'Allemande',
                    'address'        => 'Alexanderplatz 7',
                    'postal_code'    => '10178',
                    'city'           => 'Berlin',
                    'country'        => 'Germany',
                    'phone'          => '+49 30 55443322',
                    'specialization' => 'Allemand & Intégration',
                    'qualification'  => 'Master en Linguistique Appliquée',
                    'hire_date'      => '2022-09-01',
                    'notes'          => 'Formateur certifié TELC.',
                    'status'         => 'active',
                ]
            );
        }

        if ($munichCenter && isset($teacherMunich)) {
            \App\Models\Teacher::updateOrCreate(
                ['user_id' => $teacherMunich->id],
                [
                    'center_id'      => $munichCenter->id,
                    'teacher_number' => 'MUN-T-000001',
                    'first_name'     => 'Greta',
                    'last_name'      => 'Schneider',
                    'date_of_birth'  => '1988-11-05',
                    'place_of_birth' => 'Augsburg',
                    'gender'         => 'female',
                    'nationality'    => 'Allemande',
                    'address'        => 'Leopoldstraße 28',
                    'postal_code'    => '80802',
                    'city'           => 'München',
                    'country'        => 'Germany',
                    'phone'          => '+49 89 66554433',
                    'specialization' => 'Sciences & Mathématiques',
                    'qualification'  => 'Doctorat en Sciences de l’Éducation',
                    'hire_date'      => '2023-01-15',
                    'notes'          => 'Coordinatrice pédagogique.',
                    'status'         => 'active',
                ]
            );
        }

        // 12. Formations de démonstration pour Berlin et Munich
        if ($berlinCenter) {
            \App\Models\Formation::updateOrCreate(
                ['center_id' => $berlinCenter->id, 'code' => 'DE-A1'],
                [
                    'name'           => 'Deutsch A1 — Débutant',
                    'description'    => 'Apprentissage des bases de la langue allemande pour débutants (A1 selon le CECRL).',
                    'duration_hours' => 120,
                    'duration_weeks' => 8,
                    'price'          => 450.00,
                    'currency'       => 'EUR',
                    'status'         => 'active',
                ]
            );

            \App\Models\Formation::updateOrCreate(
                ['center_id' => $berlinCenter->id, 'code' => 'DE-B1'],
                [
                    'name'           => 'Deutsch B1 — Intermédiaire',
                    'description'    => 'Perfectionnement et préparation aux examens de citoyenneté et de niveau B1.',
                    'duration_hours' => 160,
                    'duration_weeks' => 10,
                    'price'          => 590.00,
                    'currency'       => 'EUR',
                    'status'         => 'active',
                ]
            );

            \App\Models\Formation::updateOrCreate(
                ['center_id' => $berlinCenter->id, 'code' => 'IT-BASIC'],
                [
                    'name'           => 'Informatique Basics',
                    'description'    => 'Initiation à l’informatique, outils bureautiques et compétences numériques de base.',
                    'duration_hours' => 60,
                    'duration_weeks' => 4,
                    'price'          => 320.00,
                    'currency'       => 'EUR',
                    'status'         => 'active',
                ]
            );
        }

        if ($munichCenter) {
            // Même code 'DE-A1' autorisé pour un centre différent !
            \App\Models\Formation::updateOrCreate(
                ['center_id' => $munichCenter->id, 'code' => 'DE-A1'],
                [
                    'name'           => 'Deutsch A1 — Grundkurs München',
                    'description'    => 'Cours intensif d’allemand débutant adapté au marché bavarois.',
                    'duration_hours' => 130,
                    'duration_weeks' => 8,
                    'price'          => 490.00,
                    'currency'       => 'EUR',
                    'status'         => 'active',
                ]
            );

            \App\Models\Formation::updateOrCreate(
                ['center_id' => $munichCenter->id, 'code' => 'EN-B2'],
                [
                    'name'           => 'English B2 — Advanced Communication',
                    'description'    => 'Perfectionnement de l’anglais professionnel et prise de parole en public.',
                    'duration_hours' => 100,
                    'duration_weeks' => 6,
                    'price'          => 520.00,
                    'currency'       => 'EUR',
                    'status'         => 'active',
                ]
            );
        }

        // 13. Classes de démonstration pour Berlin et Munich
        if ($berlinCenter) {
            $berlinDeA1 = \App\Models\Formation::where('center_id', $berlinCenter->id)->where('code', 'DE-A1')->first();
            $berlinDeB1 = \App\Models\Formation::where('center_id', $berlinCenter->id)->where('code', 'DE-B1')->first();

            if ($berlinDeA1) {
                \App\Models\ClassRoom::updateOrCreate(
                    ['center_id' => $berlinCenter->id, 'code' => 'A1-01'],
                    [
                        'formation_id' => $berlinDeA1->id,
                        'name'         => 'Groupe A1-01 — Matin',
                        'description'  => 'Cours intensifs du matin de 09h00 à 12h30.',
                        'capacity'     => 18,
                        'start_date'   => '2026-11-02',
                        'end_date'     => '2026-12-22',
                        'status'       => 'active',
                    ]
                );

                \App\Models\ClassRoom::updateOrCreate(
                    ['center_id' => $berlinCenter->id, 'code' => 'A1-02'],
                    [
                        'formation_id' => $berlinDeA1->id,
                        'name'         => 'Groupe A1-02 — Soir',
                        'description'  => 'Cours du soir de 18h00 à 20h30.',
                        'capacity'     => 15,
                        'start_date'   => '2026-11-09',
                        'end_date'     => '2026-12-30',
                        'status'       => 'active',
                    ]
                );
            }

            if ($berlinDeB1) {
                \App\Models\ClassRoom::updateOrCreate(
                    ['center_id' => $berlinCenter->id, 'code' => 'B1-01'],
                    [
                        'formation_id' => $berlinDeB1->id,
                        'name'         => 'Groupe B1-01 — Après-midi',
                        'description'  => 'Perfectionnement B1 de 14h00 à 17h15.',
                        'capacity'     => 20,
                        'start_date'   => '2026-11-16',
                        'end_date'     => '2027-01-25',
                        'status'       => 'active',
                    ]
                );
            }
        }

        if ($munichCenter) {
            $munichDeA1 = \App\Models\Formation::where('center_id', $munichCenter->id)->where('code', 'DE-A1')->first();
            $munichEnB2 = \App\Models\Formation::where('center_id', $munichCenter->id)->where('code', 'EN-B2')->first();

            if ($munichDeA1) {
                // Même code 'A1-01' autorisé à Munich dans un centre distinct !
                \App\Models\ClassRoom::updateOrCreate(
                    ['center_id' => $munichCenter->id, 'code' => 'A1-01'],
                    [
                        'formation_id' => $munichDeA1->id,
                        'name'         => 'Groupe München A1-01',
                        'description'  => 'Cours A1 Munich centre.',
                        'capacity'     => 16,
                        'start_date'   => '2026-11-02',
                        'end_date'     => '2026-12-22',
                        'status'       => 'active',
                    ]
                );
            }

            if ($munichEnB2) {
                \App\Models\ClassRoom::updateOrCreate(
                    ['center_id' => $munichCenter->id, 'code' => 'B2-01'],
                    [
                        'formation_id' => $munichEnB2->id,
                        'name'         => 'Groupe English B2-01',
                        'description'  => 'English advanced communication Munich.',
                        'capacity'     => 14,
                        'start_date'   => '2026-11-23',
                        'end_date'     => '2027-01-15',
                        'status'       => 'active',
                    ]
                );
            }
        }

        // 14. Affectations de démonstration : Professeurs -> Classes
        if ($berlinCenter) {
            $teacherBerlinModel = \App\Models\Teacher::where('center_id', $berlinCenter->id)->first();
            $berlinClassA1_01 = \App\Models\ClassRoom::where('center_id', $berlinCenter->id)->where('code', 'A1-01')->first();
            $berlinClassB1_01 = \App\Models\ClassRoom::where('center_id', $berlinCenter->id)->where('code', 'B1-01')->first();

            if ($teacherBerlinModel && $berlinClassA1_01) {
                $berlinClassA1_01->teachers()->syncWithoutDetaching([$teacherBerlinModel->id]);
            }

            if ($teacherBerlinModel && $berlinClassB1_01) {
                $berlinClassB1_01->teachers()->syncWithoutDetaching([$teacherBerlinModel->id]);
            }
        }

        if ($munichCenter) {
            $teacherMunichModel = \App\Models\Teacher::where('center_id', $munichCenter->id)->first();
            $munichClassA1_01 = \App\Models\ClassRoom::where('center_id', $munichCenter->id)->where('code', 'A1-01')->first();

            if ($teacherMunichModel && $munichClassA1_01) {
                $munichClassA1_01->teachers()->syncWithoutDetaching([$teacherMunichModel->id]);
            }
        }

        $this->command->info('✅ Plateforme initialisée : Centres, Utilisateurs, Dossiers Élèves/Profs, Formations, Classes & Affectations Professeurs.');
    }
}





