<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\User;
use Database\Seeders\DomainSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCentersSeeder extends Seeder
{
    use DemoSeederSupport;

    public function run(): void
    {
        $this->call([
            DomainSeeder::class,
            ExpenseCategorySeeder::class,
        ]);

        $centers = [
            [
                'code' => 'BERLIN',
                'name' => 'Centre de Formation Berlin',
                'address' => 'Unter den Linden 10',
                'city' => 'Berlin',
                'postal_code' => '10117',
                'country' => 'Germany',
                'phone' => '+49 30 1234567',
                'email' => 'berlin@education-centers.de',
                'status' => 'active',
            ],
            [
                'code' => 'MUNICH',
                'name' => 'Centre de Formation Munich',
                'address' => 'Marienplatz 5',
                'city' => 'Munich',
                'postal_code' => '80331',
                'country' => 'Germany',
                'phone' => '+49 89 9876543',
                'email' => 'munich@education-centers.de',
                'status' => 'active',
            ],
        ];

        foreach ($centers as $definition) {
            Center::query()->firstOrCreate(
                ['code' => $definition['code']],
                $definition,
            );
        }

        // Comptes existants : jamais de réinitialisation de mot de passe.
        User::query()->firstOrCreate(
            ['email' => 'admin@education-centers.de'],
            [
                'name' => 'Super Admin',
                'role' => User::ROLE_SUPER_ADMIN,
                'center_id' => null,
                'status' => User::STATUS_ACTIVE,
                'locale' => 'fr',
                'password' => Hash::make($this->demoPassword()),
            ],
        );

        $berlin = Center::query()->where('code', 'BERLIN')->firstOrFail();
        $munich = Center::query()->where('code', 'MUNICH')->firstOrFail();

        User::query()->firstOrCreate(
            ['email' => 'admin.berlin@education-centers.de'],
            [
                'name' => 'Admin Centre Berlin',
                'role' => User::ROLE_CENTER_ADMIN,
                'center_id' => $berlin->id,
                'status' => User::STATUS_ACTIVE,
                'locale' => 'fr',
                'password' => Hash::make($this->demoPassword()),
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'admin.munich@education-centers.de'],
            [
                'name' => 'Admin Centre Munich',
                'role' => User::ROLE_CENTER_ADMIN,
                'center_id' => $munich->id,
                'status' => User::STATUS_ACTIVE,
                'locale' => 'fr',
                'password' => Hash::make($this->demoPassword()),
            ],
        );
    }
}
