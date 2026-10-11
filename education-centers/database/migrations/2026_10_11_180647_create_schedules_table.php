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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('title')->nullable();
            $table->string('type')->default('course'); // course, exam, workshop, other
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, cancelled, completed
            $table->timestamps();

            // Index
            $table->index('center_id');
            $table->index('class_id');
            $table->index('teacher_id');
            $table->index('room_id');
            $table->index('date');
            $table->index('status');
            $table->index(['date', 'start_time']);
            $table->index(['center_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
