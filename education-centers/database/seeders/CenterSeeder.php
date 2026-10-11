<?php

namespace Database\Seeders;

use App\Models\Center;
use Illuminate\Database\Seeder;

class CenterSeeder extends Seeder
{
    /**
     * Insère les centres de démonstration.
     */
    public function run(): void
    {
        $centers = [
            [
                'name'        => 'Centre de Formation Berlin',
                'code'        => 'BERLIN',
                'address'     => 'Unter den Linden 10',
                'city'        => 'Berlin',
                'postal_code' => '10117',
                'country'     => 'Germany',
                'phone'       => '+49 30 1234567',
                'email'       => 'berlin@education-centers.de',
                'status'      => 'active',
            ],
            [
                'name'        => 'Centre de Formation Munich',
                'code'        => 'MUNICH',
                'address'     => 'Marienplatz 5',
                'city'        => 'Munich',
                'postal_code' => '80331',
                'country'     => 'Germany',
                'phone'       => '+49 89 9876543',
                'email'       => 'munich@education-centers.de',
                'status'      => 'active',
            ],
        ];

        foreach ($centers as $centerData) {
            Center::updateOrCreate(
                ['code' => $centerData['code']],
                $centerData
            );
        }

        $this->command->info('✅ Centres de démonstration créés : Berlin, Munich.');
    }
}
