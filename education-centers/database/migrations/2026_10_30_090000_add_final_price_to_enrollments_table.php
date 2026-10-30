<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prix final personnalisé accordé à l'étudiant lors de son inscription.
 *
 * Migration strictement ADDITIVE : aucune colonne existante n'est modifiée ni
 * supprimée, aucune donnée n'est recalculée. Les colonnes sont nulles pour les
 * inscriptions déjà enregistrées → elles conservent exactement le comportement
 * antérieur (le tarif officiel du catalogue reste la référence).
 *
 * - final_price     : montant réellement dû pour CETTE inscription (référence
 *                     financière de l'inscription : soldes, statuts, recouvrement,
 *                     factures, statistiques).
 * - official_price  : tarif officiel constaté au moment de l'inscription. Simple
 *                     trace de référence permettant d'afficher la réduction
 *                     accordée sans dépendre d'un catalogue qui évoluerait après
 *                     coup.
 *
 * Le catalogue (`formations.annual_price` / `formations.price`) n'est jamais
 * touché : la réduction appartient à l'inscription, pas à la formation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            if (! Schema::hasColumn('enrollments', 'official_price')) {
                $table->decimal('official_price', 10, 2)->nullable()->after('school_year');
            }

            if (! Schema::hasColumn('enrollments', 'final_price')) {
                $table->decimal('final_price', 10, 2)->nullable()->after('official_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            foreach (['final_price', 'official_price'] as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
