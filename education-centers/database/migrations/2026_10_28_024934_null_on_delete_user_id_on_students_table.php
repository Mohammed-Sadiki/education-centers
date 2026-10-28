<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifier la contrainte de clé étrangère pour nullOnDelete
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // 2. Dissocier les comptes utilisateurs des dossiers étudiants existants
        //    (les dossiers élèves restent 100% intacts avec inscriptions, paiements, etc.)
        DB::table('students')->whereNotNull('user_id')->update(['user_id' => null]);

        // 3. Supprimer les éventuelles dépendances liées aux comptes élèves si les tables existent
        $studentUserIds = DB::table('users')->where('role', User::ROLE_STUDENT)->pluck('id');
        if ($studentUserIds->isNotEmpty()) {
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->whereIn('user_id', $studentUserIds)->delete();
            }
            if (Schema::hasTable('app_notifications')) {
                DB::table('app_notifications')->whereIn('user_id', $studentUserIds)->delete();
            }
            if (Schema::hasTable('messages')) {
                DB::table('messages')->whereIn('sender_id', $studentUserIds)->delete();
            }
            if (Schema::hasTable('conversation_participants')) {
                DB::table('conversation_participants')->whereIn('user_id', $studentUserIds)->delete();
            }
            if (Schema::hasTable('audit_logs')) {
                DB::table('audit_logs')->whereIn('user_id', $studentUserIds)->update(['user_id' => null]);
            }

            // 4. Supprimer les comptes d'authentification élèves
            DB::table('users')->whereIn('id', $studentUserIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
