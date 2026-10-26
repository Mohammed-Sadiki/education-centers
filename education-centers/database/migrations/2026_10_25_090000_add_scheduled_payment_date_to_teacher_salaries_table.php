<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration : date prévue (contractuelle) de versement d'un salaire.
 *
 * Sans cette date, impossible de distinguer « En attente » d'« En retard » sans
 * inventer une échéance. La colonne est nullable : un salaire sans date prévue
 * reste « En attente », jamais « En retard » (règle métier de l'espace prof).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_salaries', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_salaries', 'scheduled_payment_date')) {
                $table->date('scheduled_payment_date')
                    ->nullable()
                    ->after('payment_date')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_salaries', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_salaries', 'scheduled_payment_date')) {
                $table->dropColumn('scheduled_payment_date');
            }
        });
    }
};
