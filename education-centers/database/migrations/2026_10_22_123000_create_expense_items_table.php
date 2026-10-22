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
        // 1. Ajouter le fournisseur (supplier) à la table expenses s'il n'existe pas
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'supplier')) {
                $table->string('supplier', 150)->nullable()->after('payment_method');
            }
        });

        // 2. Table des articles / lignes de dépenses (expense_items)
        if (! Schema::hasTable('expense_items')) {
            Schema::create('expense_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
                $table->string('name'); // ex: Feutres, Papier A4
                $table->decimal('unit_price', 10, 2);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_price', 10, 2);
                $table->timestamps();

                $table->index('expense_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_items');

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'supplier')) {
                $table->dropColumn('supplier');
            }
        });
    }
};
