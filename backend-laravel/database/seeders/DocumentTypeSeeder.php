<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // On récupère la première entreprise s'il y en a une, sinon company_id = 1 par défaut
        $companyId = DB::table('companies')->value('id') ?? 1;

        DB::table('document_types')->insert([
            ['company_id' => $companyId, 'name' => 'Contrat', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'name' => 'Facture', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'name' => 'Note de service', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'name' => 'Rapport', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'name' => 'Courrier Administratif', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}