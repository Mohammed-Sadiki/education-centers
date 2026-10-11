<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->decimal('grade', 6, 2)->nullable();
            $table->text('comment')->nullable();
            $table->date('graded_at')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->timestamps();
            $table->unique(['evaluation_id', 'student_id']);
            $table->index(['center_id', 'student_id']);
            $table->index(['evaluation_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('grades'); }
};