<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Planning des rappels de notifications internes
|--------------------------------------------------------------------------
|
| Génère les notifications liées aux dates réelles (échéances de paiement
| proches, retards, salaires à payer, présences manquantes). Anti-doublon et
| protection contre les exécutions qui se chevauchent.
|
| DÉVELOPPEMENT : php artisan schedule:work   (boucle locale, sans cron)
| PRODUCTION    : entrée cron système :
|   * * * * * cd /chemin/vers/education-centers && php artisan schedule:run >> /dev/null 2>&1
|
*/
Schedule::command('notifications:reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping(120);
