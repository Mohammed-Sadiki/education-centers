<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('formation_id')->nullable()->constrained('formations')->nullOnDelete();
            $table->string('certificate_number', 50)->unique();
            $table->enum('type', ['enrollment','completion','attendance','grades_report','receipt','other'])->default('enrollment');
            $table->string('title');
            $table->date('issued_date');
            $table->date('valid_until')->nullable();
            $table->string('file_path')->nullable();
            $table->string('verify_token', 64)->unique()->nullable();
            $table->enum('status', ['draft','issued','revoked'])->default('draft');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['center_id', 'student_id']);
            $table->index(['verify_token']);
        });
    }
    public function down(): void { Schema::dropIfExists('certificates'); }
};