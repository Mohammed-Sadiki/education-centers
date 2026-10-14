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
        Schema::create('stagiaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            
            // Informations personnelles
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->string('nationality')->nullable()->default('Marocaine');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Identité (CIN)
            $table->string('cin_number')->nullable()->index();
            $table->date('cin_issued_date')->nullable();
            $table->string('cin_issued_place')->nullable();
            $table->string('cin_recto_path')->nullable();
            $table->string('cin_verso_path')->nullable();

            // Informations du stage
            $table->date('stage_start_date')->nullable();
            $table->date('stage_end_date')->nullable();
            $table->string('stage_type')->nullable(); // Ex: Stage d'observation, Stage pratique, Stage de fin d'études
            $table->decimal('stage_price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('EUR');
            $table->string('status')->default('nouveau'); // nouveau, en_cours, termine, suspendu
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['center_id', 'status']);
        });

        Schema::create('stagiaire_diplomas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('stagiaires')->cascadeOnDelete();
            $table->string('title');
            $table->string('institution')->nullable();
            $table->string('year', 10)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();
        });

        Schema::create('stagiaire_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('stagiaires')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('autre'); // certificat, attestation, convention, autre
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stagiaire_documents');
        Schema::dropIfExists('stagiaire_diplomas');
        Schema::dropIfExists('stagiaires');
    }
};
