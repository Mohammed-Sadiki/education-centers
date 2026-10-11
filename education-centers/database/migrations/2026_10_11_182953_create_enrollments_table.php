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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('registration_number', 50)->unique();
            $table->date('application_date');
            $table->string('status', 30)->default('pending'); // pending, under_review, accepted, rejected, cancelled
            $table->text('notes')->nullable();
            $table->date('decision_date')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Index
            $table->index('center_id');
            $table->index('student_id');
            $table->index('formation_id');
            $table->index('class_id');
            $table->index('status');
            $table->index('application_date');
            $table->index(['center_id', 'status']);
            $table->index(['student_id', 'formation_id', 'status']);
            $table->index(['class_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
