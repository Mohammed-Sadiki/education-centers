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
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 8);
            $table->string('field', 64);
            $table->text('value');
            $table->string('source', 16)->default('machine');
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'locale', 'field'],
                'content_translations_unique',
            );
            $table->index(['locale', 'field']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
