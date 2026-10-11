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

        $this->command->info('✅ Comptes de démonstration créés : Prof Berlin, Prof Munich, Élève Berlin, Élève Munich + Dossiers étudiants + Dossiers professeurs.');
    }
}


