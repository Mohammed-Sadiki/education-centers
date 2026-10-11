<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ajoute center_id à la table users.
     * - nullable : permet au Super Admin (center_id = NULL) d'accéder à tous les centres.
     * - Foreign key : référence centers.id
     * - onDelete('set null') : si un centre est supprimé, les utilisateurs ne sont pas supprimés,
     *   leur center_id devient NULL → stratégie sûre côté données.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('center_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('centers')
                  ->nullOnDelete();

            $table->index('center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['center_id']);
            $table->dropIndex(['center_id']);
            $table->dropColumn('center_id');
        });
    }
};
