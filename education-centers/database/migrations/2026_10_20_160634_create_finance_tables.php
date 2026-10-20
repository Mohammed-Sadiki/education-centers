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
        // 1. Ajouter le domaine et le prix annuel aux formations s'ils n'existent pas
        Schema::table('formations', function (Blueprint $table) {
            if (! Schema::hasColumn('formations', 'domain')) {
                $table->string('domain', 50)->nullable()->after('center_id')->index();
            }
            if (! Schema::hasColumn('formations', 'annual_price')) {
                $table->decimal('annual_price', 10, 2)->nullable()->after('price');
            }
        });

        // 2. Ajouter le domaine et le salaire mensuel de base aux professeurs s'ils n'existent pas
        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'domain')) {
                $table->string('domain', 50)->nullable()->after('center_id')->index();
            }
            if (! Schema::hasColumn('teachers', 'monthly_salary')) {
                $table->decimal('monthly_salary', 10, 2)->nullable()->after('qualification');
            }
        });

        // 3. Ajouter l'année scolaire et le domaine aux paiements existants
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'school_year')) {
                $table->string('school_year', 20)->default('2026/2027')->after('invoice_id')->index();
            }
            if (! Schema::hasColumn('payments', 'domain')) {
                $table->string('domain', 50)->nullable()->after('school_year')->index();
            }
        });

        // 4. Ajouter l'année scolaire aux inscriptions (enrollments)
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'school_year')) {
                $table->string('school_year', 20)->default('2026/2027')->after('class_id')->index();
            }
        });

        // 5. Créer la table des salaires de professeurs (teacher_salaries)
        if (! Schema::hasTable('teacher_salaries')) {
            Schema::create('teacher_salaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->string('domain', 50)->nullable()->index();
                $table->unsignedTinyInteger('month'); // 1 to 12
                $table->unsignedSmallInteger('year'); // e.g. 2026, 2027
                $table->string('school_year', 20)->default('2026/2027')->index();
                $table->decimal('expected_amount', 10, 2)->default(0.00);
                $table->decimal('paid_amount', 10, 2)->default(0.00);
                $table->string('currency', 10)->default('MAD');
                $table->enum('status', ['paid', 'partial', 'unpaid'])->default('unpaid');
                $table->date('payment_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['center_id', 'teacher_id', 'year', 'month'], 'teacher_salary_unique_month');
                $table->index(['center_id', 'school_year']);
                $table->index(['center_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_salaries');

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'school_year')) {
                $table->dropColumn('school_year');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'domain')) {
                $table->dropColumn('domain');
            }
            if (Schema::hasColumn('payments', 'school_year')) {
                $table->dropColumn('school_year');
            }
        });

        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'monthly_salary')) {
                $table->dropColumn('monthly_salary');
            }
            if (Schema::hasColumn('teachers', 'domain')) {
                $table->dropColumn('domain');
            }
        });

        Schema::table('formations', function (Blueprint $table) {
            if (Schema::hasColumn('formations', 'annual_price')) {
                $table->dropColumn('annual_price');
            }
            if (Schema::hasColumn('formations', 'domain')) {
                $table->dropColumn('domain');
            }
        });
    }
};
