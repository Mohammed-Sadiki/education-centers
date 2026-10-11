<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('formation_id')->nullable()->constrained('formations')->nullOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 10)->default('EUR');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft','sent','paid','cancelled','overdue'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['center_id', 'student_id']);
            $table->index(['center_id', 'status']);
        });
        // Ajouter FK invoice_id -> invoices dans payments
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
        Schema::dropIfExists('invoices');
    }
};