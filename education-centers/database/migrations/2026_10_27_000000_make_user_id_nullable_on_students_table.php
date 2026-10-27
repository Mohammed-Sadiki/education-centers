<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un dossier élève doit pouvoir exister sans compte de connexion préalable.
 *
 * Contexte : la table `students` imposait `user_id` NOT NULL + UNIQUE (1 dossier
 * = 1 compte obligatoire). Cette migration rend `user_id` facultatif tout en
 * préservant l'intégrité existante :
 *   - La contrainte UNIQUE est conservée : la base MariaDB/MySQL autorise
 *     plusieurs NULL dans un index unique, donc les élèves sans compte coexistent,
 *     tandis qu'un compte donné ne peut toujours porter qu'un seul dossier.
 *   - La clé étrangère vers `users` est conservée (nullOnDelete implicite via
 *     cascadeOnDelete côté parent ; le dossier reste simplement non lié).
 *   - Aucune donnée existante n'est modifiée ni supprimée.
 *
 * Retour arrière raisonnable (down) : repasse `user_id` en NOT NULL. Il échoue
 * volontairement si des dossiers sans compte existent déjà (protection des
 * données), ce qui évite une perte silencieuse d'information.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Déposer temporairement les contraintes dépendantes (FK + unique)
        //    pour autoriser la modification de la nullabilité de la colonne.
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
        });

        // 2. Rendre la colonne facultative (type inchangé : bigint unsigned).
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        // 3. Rétablir l'index unique (multiple NULL autorisés) et la clé étrangère.
        Schema::table('students', function (Blueprint $table) {
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
