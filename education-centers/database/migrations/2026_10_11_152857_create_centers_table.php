<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('name');                              // Nom du centre (obligatoire)
            $table->string('code', 20)->unique();               // Code unique ex: BERLIN, MUNICH

            // Coordonnées
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Germany'); // Valeur par défaut Allemagne
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();

            // Média
            $table->string('logo')->nullable();                  // Chemin relatif du logo stocké

            // Statut
            $table->enum('status', ['active', 'inactive'])->default('active');

            // Timestamps
            $table->timestamps();

            // Index
            $table->index('status');
            $table->index('city');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
