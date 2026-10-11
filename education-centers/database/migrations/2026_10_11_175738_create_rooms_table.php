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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity');
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->text('equipment')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->unique(['center_id', 'code']);
            $table->index('center_id');
            $table->index('status');
            $table->index('capacity');
            $table->index(['center_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
