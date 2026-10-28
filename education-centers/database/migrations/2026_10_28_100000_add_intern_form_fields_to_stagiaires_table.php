<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajout (purement additif) des colonnes requises par la refonte du formulaire
     * « Ajouter un stagiaire » :
     *   - whatsapp                 : numéro WhatsApp distinct du téléphone classique ;
     *   - emergency_contact_phone  : numéro de téléphone d'urgence ;
     *   - profile_photo_path       : chemin de la photo de profil (disque privé) ;
     *   - company_name / company_phone / company_location : l'entreprise où le
     *     stagiaire effectue son stage, rattachée au dossier du stagiaire.
     *
     * Aucune suppression, aucune reconstruction : les stagiaires, leurs documents
     * et leurs informations existants restent intacts. Toutes les colonnes sont
     * nullable pour préserver les dossiers déjà enregistrés.
     */
    public function up(): void
    {
        Schema::table('stagiaires', function (Blueprint $table) {
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->string('emergency_contact_phone', 30)->nullable()->after('whatsapp');
            $table->string('profile_photo_path')->nullable()->after('emergency_contact_phone');

            $table->string('company_name')->nullable()->after('profile_photo_path');
            $table->string('company_phone', 30)->nullable()->after('company_name');
            $table->string('company_location')->nullable()->after('company_phone');
        });
    }

    public function down(): void
    {
        Schema::table('stagiaires', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp',
                'emergency_contact_phone',
                'profile_photo_path',
                'company_name',
                'company_phone',
                'company_location',
            ]);
        });
    }
};
