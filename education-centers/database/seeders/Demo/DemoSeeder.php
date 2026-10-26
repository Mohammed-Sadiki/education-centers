<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoCentersSeeder::class,
            DemoFormationsSeeder::class,
            DemoTeachersSeeder::class,
            DemoClassesSeeder::class,
            DemoStudentsSeeder::class,
            DemoEnrollmentsSeeder::class,
            DemoAcademicSeeder::class,
            DemoFinanceSeeder::class,
            DemoDocumentsSeeder::class,
        ]);
    }
}
