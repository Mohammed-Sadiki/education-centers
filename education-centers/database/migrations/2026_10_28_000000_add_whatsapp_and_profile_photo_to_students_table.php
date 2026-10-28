<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajout (purement additif) de deux colonnes optionnelles au dossier étudiant,
     * requises par le formulaire « Ajouter une formation » refondu :
     *   - whatsapp            : numéro WhatsApp distinct du téléphone classique ;
     *   - profile_photo_path  : chemin de la photo de profil (disque privé).
     *
     * Aucune suppression, aucune reconstruction : `migrate:fresh` n'est jamais
     * utilisé et les données existantes restent intactes. Les colonnes sont
     * nullable pour préserver les dossiers étudiants antérieurs.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->string('profile_photo_path')->nullable()->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'profile_photo_path']);
        });
    }
};
