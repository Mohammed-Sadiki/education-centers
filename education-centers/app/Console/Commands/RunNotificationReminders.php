<?php

namespace App\Console\Commands;

use App\Services\ReminderNotificationService;
use Illuminate\Console\Command;

class RunNotificationReminders extends Command
{
    protected $signature = 'notifications:reminders
                            {--only= : Limiter à un type (due, overdue, salary, attendance)}';

    protected $description = 'Génère les rappels de notification internes à partir des dates réellement enregistrées (échéances, retards, salaires, présences).';

    public function handle(ReminderNotificationService $reminders): int
    {
        $only = $this->option('only');
        $report = [];

        $run = function (string $key, callable $fn) use (&$report, $only): void {
            if ($only !== null && $only !== '' && $only !== $key) {
                return;
            }
            $report[$key] = $fn();
        };

        $run('due', fn () => $reminders->paymentDueSoon());
        $run('overdue', fn () => $reminders->paymentOverdue());
        $run('salary', fn () => $reminders->salaryDueSoon());
        $run('attendance', fn () => $reminders->missingAttendance());

        foreach ($report as $key => $count) {
            $this->info(sprintf('Rappels [%s] : %d notification(s) créée(s).', $key, $count));
        }

        return self::SUCCESS;
    }
}
