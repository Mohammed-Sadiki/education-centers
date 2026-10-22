<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Fournitures scolaires', 'code' => 'fournitures', 'color' => '#0DAD98', 'description' => 'Papeterie, feutres, rames A4, stylos'],
            ['name' => 'Matériel informatique', 'code' => 'informatique', 'color' => '#3B82F6', 'description' => 'Imprimantes, cartouches, câbles, écrans'],
            ['name' => 'Entretien & Nettoyage', 'code' => 'entretien', 'color' => '#F59E0B', 'description' => 'Produits d\'entretien, maintenance des locaux'],
            ['name' => 'Électricité', 'code' => 'electricite', 'color' => '#EAB308', 'description' => 'Factures d\'énergie électrique des centres'],
            ['name' => 'Eau', 'code' => 'eau', 'color' => '#06B6D4', 'description' => 'Consommation d\'eau des centres de formation'],
            ['name' => 'Internet & Télécom', 'code' => 'internet', 'color' => '#8B5CF6', 'description' => 'Fibre optique, abonnements téléphoniques'],
            ['name' => 'Loyer & Charges locatives', 'code' => 'loyer', 'color' => '#EC4899', 'description' => 'Baux commerciaux et charges immobilières'],
            ['name' => 'Autres charges d\'exploitation', 'code' => 'autres', 'color' => '#64748B', 'description' => 'Frais divers d\'exploitation et imprévus'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
