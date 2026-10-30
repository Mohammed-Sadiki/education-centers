<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DomainSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Comptes de démonstration — PROFESSEURS.
 *
 * Crée (ou retrouve) des comptes professeur de démonstration pour les quatre
 * centres (Tanger, Agadir, Essaouira, Tétouane). Pour chaque centre, un
 * professeur par domaine d'enseignement (six domaines), dont deux couvrent un
 * second domaine pour illustrer les affectations multidomaines.
 *
 * Chaque professeur est rattaché à son centre (User + dossier Teacher) et,
 * selon les relations EXISTANTES, affecté aux classes de son centre dont la
 * formation relève de ses domaines. Aucune classe/formation n'est inventée :
 * si un centre ne propose pas encore de classes, le compte reste valide mais
 * son espace est simplement vide.
 *
 * Idempotent : rejouable sans doublon. Ne touche jamais aux comptes réels, ne
 * réinitialise aucun mot de passe existant, ne supprime aucune donnée.
 * Réservé aux environnements de développement / démonstration.
 *
 * Usage :
 *   php artisan db:seed --class=DemoTeacherAccountsSeeder
 */
class DemoTeacherAccountsSeeder extends Seeder
{
    use DemoAccountSupport;

    public function run(): void
    {
        DemoAccountFixtures::assertDemoEnvironmentAllowed($this);

        // Les six domaines doivent exister pour les affectations multidomaines.
        $this->call(DomainSeeder::class);

        $created = 0;
        $existing = 0;

        foreach (DemoAccountFixtures::centers() as $center) {
            $model = Center::query()->where('code', $center['code'])->first();

            if (! $model) {
                $this->command?->warn(
                    "  ⚠ Centre « {$center['code']} » introuvable — professeurs démo ignorés. Lancez CenterSeeder d'abord."
                );

                continue;
            }

            $this->command?->line("  <comment>{$center['label']} ({$model->code})</comment>");

            foreach (DemoAccountFixtures::teacherRoster() as $index => $profile) {
                [$user, $wasCreated] = $this->ensureTeacherAccount($model, $center, $profile, $index);

                $wasCreated ? $created++ : $existing++;
                $this->command?->info(
                    '    ' . ($wasCreated ? '✔ créé   ' : '· existant ')
                    . $user->email . '  [' . $profile['domain'] . ']'
                );
            }
        }

        $this->command?->info(
            "✅ Comptes professeur démo : {$created} créé(s), {$existing} déjà présent(s)."
        );
    }

    /**
     * @param  array{code:string, city:string, label:string}  $center
     * @param  array<string, mixed>  $profile
     * @return array{0: User, 1: bool}
     */
    private function ensureTeacherAccount(Center $center, array $centerDef, array $profile, int $index): array
    {
        $domains = array_values(array_unique([$profile['domain'], ...$profile['secondary']]));

        // 1. Compte de connexion (User) — idempotent, jamais de reset du mot de passe.
        $result = $this->ensureDemoAccount(
            DemoAccountFixtures::teacherEmail($centerDef['city'], $profile['domain']),
            'Prof. ' . $profile['first_name'] . ' ' . $profile['last_name'] . ' (Demo)',
            User::ROLE_TEACHER,
            (int) $center->id,
        );

        $user = $result['user'];

        // 2. Dossier professeur, repéré par un teacher_number DEMO déterministe.
        $teacherNumber = sprintf('DEMO-%s-T-%02d', $center->code, $index + 1);

        $teacher = Teacher::query()->updateOrCreate(
            [
                'center_id' => (int) $center->id,
                'teacher_number' => $teacherNumber,
            ],
            [
                'user_id' => $user->id,
                'domain' => $profile['domain'],
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'date_of_birth' => date('Y-m-d', strtotime('1985-04-10') + ($index * 400) * 86400),
                'place_of_birth' => $centerDef['label'],
                'gender' => $profile['gender'],
                'nationality' => 'Marocaine',
                'address' => 'Avenue de la Formation ' . ($index + 1),
                'postal_code' => (string) ($center->postal_code ?: '90000'),
                'city' => $centerDef['label'],
                'country' => 'Morocco',
                'phone' => $center->phone,
                'specialization' => DemoFixtures::domainSpecialization($profile['domain']),
                'qualification' => DemoFixtures::domainQualification($profile['domain']),
                'monthly_salary' => 6500.00 + ($index * 150),
                'hire_date' => date('Y-m-d', strtotime('2019-09-01') + ($index * 120) * 86400),
                'notes' => 'Compte professeur de démonstration – aucune donnée réelle.',
                'status' => 'active',
            ],
        );

        // 3. Domaines d'enseignement (principal + secondaires) sur l'utilisateur et le dossier.
        $user->unsetRelation('teacher');
        $user->syncTeachingDomains($domains);

        // 4. Affectation aux classes EXISTANTES du centre, selon les domaines.
        $this->assignClasses($teacher, $center, $domains);

        return [$user, $result['created']];
    }

    /**
     * Attache le professeur aux classes de SON centre dont la formation relève
     * de ses domaines. Additif et idempotent : n'écrase aucune affectation existante.
     *
     * @param  list<string>  $domains
     */
    private function assignClasses(Teacher $teacher, Center $center, array $domains): void
    {
        foreach ($domains as $domain) {
            $class = ClassRoom::query()
                ->where('classes.center_id', (int) $center->id)
                ->whereHas('formation', fn ($q) => $q->where('domain', $domain))
                ->orderBy('id')
                ->first();

            if (! $class) {
                continue;
            }

            $alreadyLinked = DB::table('class_teacher')
                ->where('class_id', $class->id)
                ->where('teacher_id', $teacher->id)
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            DB::table('class_teacher')->insert([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
