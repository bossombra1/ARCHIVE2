<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PosteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('postes')->insert([
            ['name' => 'Administrateur Système', 'level' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Directeur Général', 'level' => 'dg', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Directeur', 'level' => 'directeur', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Responsable Département', 'level' => 'responsable_departement', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chef de Service', 'level' => 'chef_service', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Employé Ordinaire', 'level' => 'employe', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Agent Temporaire', 'level' => 'agent_temporaire', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
