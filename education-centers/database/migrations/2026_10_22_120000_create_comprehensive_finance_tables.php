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
        // 1. Table des catégories de dépenses
        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->unique();
                $table->string('color', 20)->default('#0DAD98');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Table des dépenses / charges (décaissements d'exploitation)
        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->string('domain', 50)->nullable()->index();
                $table->string('school_year', 20)->default('2026/2027')->index();
                $table->string('title');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('MAD');
                $table->date('expense_date');
                $table->enum('payment_method', ['cash', 'check', 'bank_transfer', 'credit_card', 'other'])->default('bank_transfer');
                $table->string('reference')->nullable();
                $table->string('receipt_path')->nullable();
                $table->text('description')->nullable();
                $table->enum('status', ['paid', 'pending', 'cancelled'])->default('paid');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['center_id', 'expense_date']);
                $table->index(['center_id', 'school_year']);
            });
        }

        // 3. Table des écritures de compte bancaire & caisse (trésorerie)
        if (! Schema::hasTable('bank_transactions')) {
            Schema::create('bank_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
                $table->date('transaction_date');
                $table->string('label');
                $table->string('origin')->default('Banque'); // Caisse, Banque Centrale, Étudiant, Salaire, Dépense
                $table->enum('method', ['bank_transfer', 'check', 'cash', 'credit_card', 'other'])->default('bank_transfer');
                $table->enum('type', ['deposit', 'withdrawal']); // Dépôt (crédit) ou Retrait (débit)
                $table->decimal('amount', 10, 2);
                $table->decimal('running_balance', 12, 2)->default(0.00);
                $table->string('reference')->nullable();
                $table->string('receipt_path')->nullable();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('teacher_salary_id')->nullable()->constrained('teacher_salaries')->nullOnDelete();
                $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['center_id', 'transaction_date']);
            });
        }

        // 4. Table des écritures comptables (Journal général - partie double simplifiée)
        if (! Schema::hasTable('accounting_entries')) {
            Schema::create('accounting_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
                $table->string('piece_number', 50)->nullable()->index();
                $table->date('entry_date');
                $table->string('entry_type', 50); // Recette, Dépense, Salaire, A-nouveaux, Virement interne
                $table->string('label');
                $table->string('third_party')->nullable(); // Nom de l'étudiant, enseignant, fournisseur
                $table->string('account_code', 20)->default('5141'); // Ex: 7124 Scolarités, 6171 Salaires, 5141 Banque, 5161 Caisse
                $table->decimal('debit', 10, 2)->default(0.00);
                $table->decimal('credit', 10, 2)->default(0.00);
                $table->decimal('cumulative_balance', 12, 2)->default(0.00);
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['center_id', 'entry_date']);
                $table->index(['account_code']);
            });
        }

        // 5. Table des budgets prévisionnels par centre et année
        if (! Schema::hasTable('center_budgets')) {
            Schema::create('center_budgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
                $table->string('school_year', 20)->default('2026/2027')->index();
                $table->decimal('annual_allocated_budget', 12, 2)->default(500000.00);
                $table->timestamps();

                $table->unique(['center_id', 'school_year']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_budgets');
        Schema::dropIfExists('accounting_entries');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
