<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('formation_id')->nullable()->constrained('formations')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable(); // FK ajoutee apres invoices
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('EUR');
            $table->date('payment_date');
            $table->enum('method', ['cash','card','bank_transfer','other'])->default('cash');
            $table->string('reference')->nullable();
            $table->enum('status', ['pending','paid','cancelled','refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['center_id', 'student_id']);
            $table->index(['center_id', 'status']);
            $table->index(['enrollment_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};