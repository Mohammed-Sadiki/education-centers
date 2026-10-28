<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration additive : table pivot user_domains.
 *
 * Associe les utilisateurs (notamment les professeurs créés via le formulaire
 * d'utilisateurs) à un ou plusieurs domaines d'enseignement (domains.slug).
 *
 * Additif, aucune suppression de données existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('domain_slug', 50);
            $table->foreign('domain_slug')
                ->references('slug')
                ->on('domains')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unique(['user_id', 'domain_slug'], 'user_domains_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_domains');
    }
};
