<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En environnement de test (SQLite in-memory), les migrations définissent seulement le schéma.
        // Les seeders et tests créent leurs propres données.
        if (app()->environment('testing')) {
            return;
        }

        // 1. Mise à jour du Centre Berlin -> Centre Tanger (ID 1)
        DB::table('centers')->where('id', 1)->update([
            'name' => 'Centre Tanger',
            'code' => 'TANGER',
            'city' => 'Tanger',
            'country' => 'Morocco',
            'phone' => '+212 5 39 00 00 01',
            'email' => 'tanger@education-centers.ma',
            'address' => 'Boulevard Mohammed V, Tanger',
            'postal_code' => '90000',
            'status' => 'active',
            'updated_at' => now(),
        ]);

        // Mise à jour de l'email et du nom de l'admin du centre 1
        DB::table('users')->where('id', 2)->update([
            'name' => 'Admin Centre Tanger',
            'email' => 'admin.tanger@education-centers.ma',
            'updated_at' => now(),
        ]);

        // 2. Mise à jour du Centre Munich -> Centre Essaouira (ID 2)
        DB::table('centers')->where('id', 2)->update([
            'name' => 'Centre Essaouira',
            'code' => 'ESSAOUIRA',
            'city' => 'Essaouira',
            'country' => 'Morocco',
            'phone' => '+212 5 24 00 00 02',
            'email' => 'essaouira@education-centers.ma',
            'address' => 'Avenue de la Médina, Essaouira',
            'postal_code' => '44000',
            'status' => 'active',
            'updated_at' => now(),
        ]);

        // Mise à jour de l'email et du nom de l'admin du centre 2
        DB::table('users')->where('id', 3)->update([
            'name' => 'Admin Centre Essaouira',
            'email' => 'admin.essaouira@education-centers.ma',
            'updated_at' => now(),
        ]);

        // 3. Créer ou mettre à jour Centre Agadir
        $agadirCenter = DB::table('centers')->where('code', 'AGADIR')->first();
        if (! $agadirCenter) {
            $agadirId = DB::table('centers')->insertGetId([
                'name' => 'Centre Agadir',
                'code' => 'AGADIR',
                'city' => 'Agadir',
                'country' => 'Morocco',
                'phone' => '+212 5 28 00 00 03',
                'email' => 'agadir@education-centers.ma',
                'address' => 'Boulevard Hassan II, Agadir',
                'postal_code' => '80000',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $agadirId = $agadirCenter->id;
            DB::table('centers')->where('id', $agadirId)->update([
                'name' => 'Centre Agadir',
                'city' => 'Agadir',
                'country' => 'Morocco',
                'updated_at' => now(),
            ]);
        }

        // Admin Agadir
        $adminAgadir = DB::table('users')->where('email', 'admin.agadir@education-centers.ma')->first();
        if (! $adminAgadir) {
            DB::table('users')->insert([
                'name' => 'Admin Centre Agadir',
                'email' => 'admin.agadir@education-centers.ma',
                'password' => Hash::make('password'),
                'role' => 'center_admin',
                'center_id' => $agadirId,
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('users')->where('id', $adminAgadir->id)->update([
                'center_id' => $agadirId,
                'role' => 'center_admin',
                'status' => 'active',
                'updated_at' => now(),
            ]);
        }

        // 4. Créer ou mettre à jour Centre Tétouane
        $tetouaneCenter = DB::table('centers')->where('code', 'TETOUANE')->first();
        if (! $tetouaneCenter) {
            $tetouaneId = DB::table('centers')->insertGetId([
                'name' => 'Centre Tétouane',
                'code' => 'TETOUANE',
                'city' => 'Tétouane',
                'country' => 'Morocco',
                'phone' => '+212 5 39 00 00 04',
                'email' => 'tetouane@education-centers.ma',
                'address' => 'Avenue Mohammed V, Tétouane',
                'postal_code' => '93000',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $tetouaneId = $tetouaneCenter->id;
            DB::table('centers')->where('id', $tetouaneId)->update([
                'name' => 'Centre Tétouane',
                'city' => 'Tétouane',
                'country' => 'Morocco',
                'updated_at' => now(),
            ]);
        }

        // Admin Tétouane
        $adminTetouane = DB::table('users')->where('email', 'admin.tetouane@education-centers.ma')->first();
        if (! $adminTetouane) {
            DB::table('users')->insert([
                'name' => 'Admin Centre Tétouane',
                'email' => 'admin.tetouane@education-centers.ma',
                'password' => Hash::make('password'),
                'role' => 'center_admin',
                'center_id' => $tetouaneId,
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('users')->where('id', $adminTetouane->id)->update([
                'center_id' => $tetouaneId,
                'role' => 'center_admin',
                'status' => 'active',
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert names to Berlin and Munich for test isolation
        DB::table('centers')->where('id', 1)->update([
            'name' => 'Centre de Formation Berlin',
            'code' => 'BERLIN',
            'city' => 'Berlin',
            'country' => 'Germany',
            'email' => 'berlin@education-centers.de',
        ]);
        DB::table('centers')->where('id', 2)->update([
            'name' => 'Centre de Formation Munich',
            'code' => 'MUNICH',
            'city' => 'Munich',
            'country' => 'Germany',
            'email' => 'munich@education-centers.de',
        ]);
        DB::table('users')->where('email', 'admin.agadir@education-centers.ma')->delete();
        DB::table('users')->where('email', 'admin.tetouane@education-centers.ma')->delete();
        DB::table('centers')->where('code', 'AGADIR')->delete();
        DB::table('centers')->where('code', 'TETOUANE')->delete();
    }
};
