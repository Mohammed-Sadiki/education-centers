<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->time('arrival_time')->nullable();
            $table->unsignedSmallInteger('late_minutes')->nullable();
            $table->string('justification')->nullable();
            $table->text('comment')->nullable();
            $table->enum('justification_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'schedule_id'], 'unique_student_schedule_attendance');
            $table->index(['center_id', 'date']);
            $table->index(['student_id', 'date']);
            $table->index(['class_id', 'date']);
            $table->index(['schedule_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
