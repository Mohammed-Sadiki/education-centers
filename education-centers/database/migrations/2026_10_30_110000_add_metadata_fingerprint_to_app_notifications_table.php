<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complément d'infrastructure pour le système de notifications internes.
 *
 * Migration strictement ADDITIVE : aucune colonne ni donnée existante n'est
 * modifiée ni supprimée. Les nouvelles colonnes sont nulles pour les
 * notifications déjà enregistrées, qui conservent exactement leur comportement.
 *
 * - metadata    : utilitaires structurés (JSON) sans données sensibles
 *                 inutiles (ex. montant, nombres, ids techniques) pour
 *                 l'affichage et le traitement futur.
 * - fingerprint : empreinte de déduplication. Deux notifications identiques
 *                 (même destinataire, type et entité liée) partagent la même
 *                 empreinte, ce qui permet d'éviter les doublons lors des
 *                 nouvelles tentatives / modifications de requêtes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('app_notifications', 'metadata')) {
                $table->json('metadata')->nullable()->after('related_id');
            }

            if (! Schema::hasColumn('app_notifications', 'fingerprint')) {
                $table->string('fingerprint', 64)->nullable()->after('metadata');
            }
        });

        // Index de déduplication (faible cardinalité par utilisateur).
        if (! Schema::hasIndex('app_notifications', ['user_id', 'fingerprint'])) {
            Schema::table('app_notifications', function (Blueprint $table): void {
                $table->index(['user_id', 'fingerprint'], 'app_notifications_user_fingerprint_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            foreach (['app_notifications_user_fingerprint_index'] as $index) {
                if (Schema::hasIndex('app_notifications', ['user_id', 'fingerprint'])) {
                    $table->dropIndex($index);
                }
            }

            foreach (['fingerprint', 'metadata'] as $column) {
                if (Schema::hasColumn('app_notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
