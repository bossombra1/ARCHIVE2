<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\Poste;
use App\Models\Service;
use App\Models\Affectation;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer une entreprise test (par exemple une petite entreprise)
        $company = Company::create([
            'name' => 'GED Entreprise Test',
            'size' => 'small',
            'is_configured' => true
        ]);

        // 2. Créer un service de base
        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Direction Informatique'
        ]);

        // 3. Récupérer le poste Admin
        $posteAdmin = Poste::where('level', 'admin')->first();

        // 4. Créer l'utilisateur Administrateur
        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Super Administrateur',
            'email' => 'admin@ged.com',
            'password' => Hash::make('password123'),
            'status' => true // Actif
        ]);

        // 5. Lier l'admin à son poste via l'affectation
        Affectation::create([
            'user_id' => $admin->id,
            'poste_id' => $posteAdmin->id,
            'service_id' => $service->id,
            'is_active' => true,
            'started_at' => now(),
        ]);
    }
}