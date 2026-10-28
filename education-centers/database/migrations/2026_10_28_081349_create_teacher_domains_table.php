<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration additive : crée la table pivot teacher_domains.
 *
 * Lie les professeurs (teachers.id) aux slugs de domaine (domains.slug)
 * pour permettre la sélection multiple de domaines d'enseignement.
 *
 * Rétro-compatibilité : les professeurs déjà créés avec une colonne `domain`
 * non nulle ont leurs données migrées automatiquement dans le pivot.
 *
 * CONTRAINTES :
 *  - Pas de migrate:fresh
 *  - Aucune suppression de données existantes
 *  - Aucune modification d'autres tables
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Créer la table pivot teacher_domains
        Schema::create('teacher_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();
            $table->string('domain_slug', 50);
            $table->foreign('domain_slug')
                ->references('slug')
                ->on('domains')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unique(['teacher_id', 'domain_slug'], 'teacher_domains_unique');
            $table->timestamps();
        });

        // 2. Migrer les domaines principaux existants (teachers.domain) dans le pivot
        //    Seuls les domaines dont le slug existe dans la table domains sont migrés.
        $validSlugs = DB::table('domains')->pluck('slug')->toArray();

        if (! empty($validSlugs)) {
            $teachers = DB::table('teachers')
                ->whereNotNull('domain')
                ->whereIn('domain', $validSlugs)
                ->get(['id', 'domain', 'created_at']);

            $now = now()->toDateTimeString();

            foreach ($teachers as $teacher) {
                DB::table('teacher_domains')->insertOrIgnore([
                    'teacher_id'  => $teacher->id,
                    'domain_slug' => $teacher->domain,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_domains');
    }
};
