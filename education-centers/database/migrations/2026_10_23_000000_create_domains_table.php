<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table des domaines de formation affichés sur le portail Super Admin.
     * Les domaines ne sont plus codés en dur dans la vue : ils sont lus ici.
     */
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();      // identifiant de route (ex : sante)
            $table->string('name');                 // libellé affiché (FR par défaut)
            $table->text('description')->nullable(); // description de la carte
            $table->string('icon')->default('home'); // nom d'icône (composant x-icon)
            $table->string('color')->default('brand'); // palette Tailwind (emerald, blue…)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
