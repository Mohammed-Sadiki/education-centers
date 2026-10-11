<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['exam','control','homework','oral','project','participation'])->default('exam');
            $table->date('evaluation_date');
            $table->decimal('max_grade', 6, 2)->default(20);
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->text('description')->nullable();
            $table->enum('status', ['draft','published','closed'])->default('draft');
            $table->timestamps();
            $table->index(['center_id', 'class_id']);
            $table->index(['center_id', 'formation_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('evaluations'); }
};