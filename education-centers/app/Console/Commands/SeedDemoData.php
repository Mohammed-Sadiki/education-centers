<?php

namespace App\Console\Commands;

use Database\Seeders\Demo\DemoAcademicSeeder;
use Database\Seeders\Demo\DemoCentersSeeder;
use Database\Seeders\Demo\DemoClassesSeeder;
use Database\Seeders\Demo\DemoDocumentsSeeder;
use Database\Seeders\Demo\DemoEnrollmentsSeeder;
use Database\Seeders\Demo\DemoFinanceSeeder;
use Database\Seeders\Demo\DemoFormationsSeeder;
use Database\Seeders\Demo\DemoSeeder;
use Database\Seeders\Demo\DemoStudentsSeeder;
use Database\Seeders\Demo\DemoTeachersSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed
        {--skip-documents : Ne pas générer les PDF de démonstration}';

    protected $description = 'Crée ou complète la base de démonstration Berlin / Munich (idempotent, interdit en production)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'demo', 'testing'])) {
            $this->error('La commande demo:seed est interdite hors des environnements local, demo et testing.');

            return self::FAILURE;
        }

        $seeders = [
            DemoCentersSeeder::class,
            DemoFormationsSeeder::class,
            DemoTeachersSeeder::class,
            DemoClassesSeeder::class,
            DemoStudentsSeeder::class,
            DemoEnrollmentsSeeder::class,
            DemoAcademicSeeder::class,
            DemoFinanceSeeder::class,
        ];

        if (! $this->option('skip-documents')) {
            $seeders[] = DemoDocumentsSeeder::class;
        }

        $started = microtime(true);
        $this->components->info('Démarrage du remplissage DEMO (Berlin + Munich)...');

        try {
            DB::transaction(function () use ($seeders): void {
                $seeder = new DemoSeeder;
                $seeder->setContainer($this->laravel)->setCommand($this);
                $seeder->call($seeders);
            });
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error('Échec du seeding DEMO : '.$exception->getMessage());
            $this->line($exception->getFile().':'.$exception->getLine());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info(sprintf(
            'Seeding DEMO terminé en %.1f seconde(s). Aucun mot de passe administrateur n’a été modifié.',
            microtime(true) - $started,
        ));

        if ($this->option('skip-documents')) {
            $this->warn('Les certificats et documents PDF n’ont pas été générés (--skip-documents).');
        }

        $this->newLine();

        return $this->call('demo:verify', ['--strict' => true]);
    }
}
