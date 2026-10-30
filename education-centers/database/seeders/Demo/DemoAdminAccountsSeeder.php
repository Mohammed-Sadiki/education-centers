<?php

namespace Database\Seeders\Demo;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comptes de démonstration — ADMINISTRATEURS.
 *
 * Crée (ou retrouve) exclusivement des comptes de démonstration :
 *   - 1 Super Admin Demo ;
 *   - 1 Admin Centre Demo pour chacun des quatre centres
 *     (Tanger, Agadir, Essaouira, Tétouane).
 *
 * Idempotent : rejouable sans doublon. Ne touche jamais aux comptes réels et
 * ne réinitialise aucun mot de passe existant. Réservé aux environnements de
 * développement / démonstration (bloqué en production).
 *
 * Usage :
 *   php artisan db:seed --class=DemoAdminAccountsSeeder
 */
class DemoAdminAccountsSeeder extends Seeder
{
    use DemoAccountSupport;

    public function run(): void
    {
        DemoAccountFixtures::assertDemoEnvironmentAllowed($this);

        $created = 0;
        $existing = 0;

        // 1. Super Admin Demo (center_id = null).
        $result = $this->ensureDemoAccount(
            DemoAccountFixtures::superAdminEmail(),
            'Super Admin (Demo)',
            User::ROLE_SUPER_ADMIN,
            null,
        );
        $result['created'] ? $created++ : $existing++;
        $this->command?->info(
            ($result['created'] ? '  ✔ créé   ' : '  · existant ')
            . $result['user']->email . '  [' . User::ROLE_SUPER_ADMIN . ']'
        );

        // 2. Un Admin Centre Demo par centre, résolu par CODE (jamais par ID).
        foreach (DemoAccountFixtures::centers() as $center) {
            $model = Center::query()->where('code', $center['code'])->first();

            if (! $model) {
                $this->command?->warn(
                    "  ⚠ Centre « {$center['code']} » introuvable — compte admin « {$center['city']} » ignoré. "
                    . "Lancez CenterSeeder d'abord."
                );

                continue;
            }

            $result = $this->ensureDemoAccount(
                DemoAccountFixtures::centerAdminEmail($center['city']),
                'Admin Centre ' . $center['label'] . ' (Demo)',
                User::ROLE_CENTER_ADMIN,
                (int) $model->id,
            );
            $result['created'] ? $created++ : $existing++;
            $this->command?->info(
                ($result['created'] ? '  ✔ créé   ' : '  · existant ')
                . $result['user']->email . '  [' . User::ROLE_CENTER_ADMIN . ' · ' . $center['code'] . ']'
            );
        }

        $this->command?->info(
            "✅ Comptes admin démo : {$created} créé(s), {$existing} déjà présent(s)."
        );
    }
}
