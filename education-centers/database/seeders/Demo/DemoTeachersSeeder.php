<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTeachersSeeder extends Seeder
{
    use DemoSeederSupport;

    public function run(): void
    {
        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            $city = $center->code === 'BERLIN' ? 'Berlin' : 'Munich';
            $postal = $center->code === 'BERLIN' ? '10115' : '80331';
            $femaleNames = ['Lena', 'Sofia', 'Greta', 'Clara', 'Nora', 'Amelie'];

            foreach (DemoFixtures::teacherBlueprints($center) as $index => $blueprint) {
                $user = User::query()->firstOrCreate(
                    ['email' => $blueprint['email']],
                    [
                        'name' => 'Prof. '.$blueprint['first_name'].' '.$blueprint['last_name'],
                        'role' => User::ROLE_TEACHER,
                        'center_id' => $center->id,
                        'status' => User::STATUS_ACTIVE,
                        'locale' => 'fr',
                        'password' => $this->randomPasswordHash(),
                    ],
                );

                if ($user->role !== User::ROLE_TEACHER || (int) $user->center_id !== (int) $center->id) {
                    $user->update([
                        'role' => User::ROLE_TEACHER,
                        'center_id' => $center->id,
                    ]);
                }

                Teacher::query()->updateOrCreate(
                    [
                        'center_id' => $center->id,
                        'teacher_number' => $blueprint['teacher_number'],
                    ],
                    [
                        'user_id' => $user->id,
                        'domain' => $blueprint['domain'],
                        'first_name' => $blueprint['first_name'],
                        'last_name' => $blueprint['last_name'],
                        'date_of_birth' => date('Y-m-d', strtotime('1979-03-01') + ($index * 731) * 86400),
                        'place_of_birth' => $city,
                        'gender' => in_array($blueprint['first_name'], $femaleNames, true) ? 'female' : 'male',
                        'nationality' => $index % 3 === 0 ? 'Allemande' : ($index % 3 === 1 ? 'Française' : 'Marocaine'),
                        'address' => 'Rue de la Formation '.($index + 10),
                        'postal_code' => $postal,
                        'city' => $city,
                        'country' => 'Germany',
                        'phone' => sprintf('+49 %s %08d', $center->code === 'BERLIN' ? '30' : '89', 30000000 + ($index * 173)),
                        'specialization' => $blueprint['specialization'],
                        'qualification' => $blueprint['qualification'],
                        'monthly_salary' => $blueprint['monthly_salary'],
                        'hire_date' => date('Y-m-d', strtotime('2018-09-01') + ($index * 97) * 86400),
                        'notes' => 'Professeur fictif de démonstration – aucune donnée réelle.',
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
