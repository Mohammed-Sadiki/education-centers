<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoStudentsSeeder extends Seeder
{
    use DemoSeederSupport;

    public function run(): void
    {
        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            $existing = Student::query()->where('center_id', $center->id)->count();
            $needed = max(0, 60 - $existing);

            if ($needed === 0) {
                continue;
            }

            $blueprints = array_slice(DemoFixtures::studentBlueprints($center), 0, $needed);

            foreach ($blueprints as $blueprint) {
                $user = User::query()->firstOrCreate(
                    ['email' => $blueprint['email']],
                    [
                        'name' => $blueprint['first_name'].' '.$blueprint['last_name'],
                        'role' => User::ROLE_STUDENT,
                        'center_id' => $center->id,
                        'status' => User::STATUS_ACTIVE,
                        'locale' => 'fr',
                        'password' => $this->randomPasswordHash(),
                    ],
                );

                if ($user->role !== User::ROLE_STUDENT || (int) $user->center_id !== (int) $center->id) {
                    $user->update([
                        'role' => User::ROLE_STUDENT,
                        'center_id' => $center->id,
                    ]);
                }

                Student::query()->updateOrCreate(
                    [
                        'center_id' => $center->id,
                        'student_number' => $blueprint['student_number'],
                    ],
                    [
                        'user_id' => $user->id,
                        'first_name' => $blueprint['first_name'],
                        'last_name' => $blueprint['last_name'],
                        'date_of_birth' => $blueprint['date_of_birth'],
                        'place_of_birth' => $blueprint['place_of_birth'],
                        'gender' => $blueprint['gender'],
                        'nationality' => $blueprint['nationality'],
                        'address' => $blueprint['address'],
                        'postal_code' => $blueprint['postal_code'],
                        'city' => $blueprint['city'],
                        'country' => $blueprint['country'],
                        'phone' => $blueprint['phone'],
                        'emergency_contact_name' => $blueprint['emergency_contact_name'],
                        'emergency_contact_phone' => $blueprint['emergency_contact_phone'],
                        'emergency_contact_relation' => $blueprint['emergency_contact_relation'],
                        'notes' => $blueprint['notes'],
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
