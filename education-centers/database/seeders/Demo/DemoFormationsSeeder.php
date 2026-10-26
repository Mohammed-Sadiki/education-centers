<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\Formation;
use Illuminate\Database\Seeder;

class DemoFormationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            $legacyMap = DemoFixtures::legacyFormationCodes($center);

            // Réutilisation des anciennes formations de démonstration sans
            // suppression : leurs identifiants restent référencés par les
            // classes, inscriptions et documents existants.
            foreach ($legacyMap as $legacyCode => $targetCode) {
                $targetExists = Formation::query()
                    ->where('center_id', $center->id)
                    ->where('code', $targetCode)
                    ->exists();

                if ($targetExists) {
                    continue;
                }

                $legacy = Formation::query()
                    ->where('center_id', $center->id)
                    ->where('code', $legacyCode)
                    ->first();

                if ($legacy) {
                    $legacy->update(['code' => $targetCode]);
                }
            }

            foreach (DemoFixtures::formationBlueprints($center) as $blueprint) {
                Formation::query()->updateOrCreate(
                    [
                        'center_id' => $center->id,
                        'code' => $blueprint['code'],
                    ],
                    [
                        'domain' => $blueprint['domain'],
                        'name' => $blueprint['name'],
                        'description' => $blueprint['description'],
                        'duration_hours' => $blueprint['duration_hours'],
                        'duration_weeks' => $blueprint['duration_weeks'],
                        'price' => round($blueprint['annual_price'] / 10, 2),
                        'annual_price' => $blueprint['annual_price'],
                        'currency' => default_currency(),
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
