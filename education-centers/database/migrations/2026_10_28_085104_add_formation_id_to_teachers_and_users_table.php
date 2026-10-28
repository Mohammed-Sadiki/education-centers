<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration additive : ajoute formation_id et domain aux tables users et teachers.
 *
 * Permet d'associer directement le domaine d'enseignement et la formation enseignée
 * à un compte professeur (user) et à son dossier (teacher).
 *
 * Contraintes respectées :
 * - Migration purement additive (colonnes nullables)
 * - Aucune perte ni suppression de données
 * - nullOnDelete pour préserver l'intégrité
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter domain et formation_id sur la table users (pour les comptes professeurs)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'domain')) {
                $table->string('domain', 50)->nullable()->after('role')->index();
            }
            if (! Schema::hasColumn('users', 'formation_id')) {
                $table->foreignId('formation_id')
                    ->nullable()
                    ->after('domain')
                    ->constrained('formations')
                    ->nullOnDelete();
            }
        });

        // 2. Ajouter formation_id sur la table teachers
        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'formation_id')) {
                $table->foreignId('formation_id')
                    ->nullable()
                    ->after('domain')
                    ->constrained('formations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'formation_id')) {
                $table->dropForeign(['formation_id']);
                $table->dropColumn('formation_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'formation_id')) {
                $table->dropForeign(['formation_id']);
                $table->dropColumn('formation_id');
            }
            if (Schema::hasColumn('users', 'domain')) {
                $table->dropColumn('domain');
            }
        });
    }
};
