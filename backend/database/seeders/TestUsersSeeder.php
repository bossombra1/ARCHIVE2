<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\Company;
use App\Models\Poste;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            $this->command->error('Aucune company trouvée. Lancez d\'abord le SetupController ou le DatabaseSeeder.');
            return;
        }

        // Récupère les postes par level
        $posteAdmin = Poste::where('level', 'admin')->first();
        $posteDg = Poste::where('level', 'dg')->first();
        $posteDirecteur = Poste::where('level', 'directeur')->first();
        $posteRespDep = Poste::where('level', 'responsable_departement')->first();
        $posteChefService = Poste::where('level', 'chef_service')->first();
        $posteEmploye = Poste::where('level', 'employe')->first();
        $posteAgent = Poste::where('level', 'agent_temporaire')->first();

        // Récupère les services par nom (créés par le TestDataSeeder)
        // Si tu n'as pas lancé le TestDataSeeder, on prend les services existants.
        $services = Service::where('company_id', $company->id)->get()->keyBy('name');

        // Helper pour trouver un service ou fallback sur le premier
        $getService = function (string $name) use ($services, $company) {
            return $services[$name] ?? Service::where('company_id', $company->id)->first();
        };

        // Helper pour créer un user + son affectation
        $createUser = function (array $data, $poste, $service) use ($company) {
            $existing = User::where('email', $data['email'])->first();
            if ($existing) {
                $this->command->warn("User {$data['email']} existe déjà, skip.");
                return $existing;
            }

            $user = User::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => true,
                'lang' => 'fr',
                'theme_color' => $data['theme_color'] ?? '#2563eb',
            ]);

            // Récupère le département et la direction depuis le service
            $department = $service?->department;
            $direction = $department?->direction;

            Affectation::create([
                'user_id' => $user->id,
                'poste_id' => $poste->id,
                'service_id' => $service->id,
                'department_id' => $department?->id,
                'direction_id' => $direction?->id,
                'is_active' => true,
                'started_at' => now(),
            ]);

            return $user;
        };

        // ============================================================
        // 10 UTILISATEURS DE TEST
        // Mot de passe pour tous : password123
        // ============================================================

        // 1. ADMIN (déjà un existant via SetupController, on en crée un 2e)
        $createUser([
            'name' => ' Admin Test',
            'email' => 'admin.test@archive2.test',
            'password' => 'password123',
            'theme_color' => '#dc3545',
        ], $posteAdmin, $getService('Accueil'));

        // 2. DG (Directeur Général)
        $createUser([
            'name' => 'Jean-Marc DirecteurGénéral',
            'email' => 'dg@archive2.test',
            'password' => 'password123',
            'theme_color' => '#6f42c1',
        ], $posteDg, $getService('Accueil'));

        // 3. DIRECTEUR - Direction RH
        $createUser([
            'name' => 'Sophie Martin',
            'email' => 'directeur.rh@archive2.test',
            'password' => 'password123',
            'theme_color' => '#0d6efd',
        ], $posteDirecteur, $getService('Sourcing'));

        // 4. DIRECTEUR - Direction SI
        $createUser([
            'name' => 'Karim Benali',
            'email' => 'directeur.si@archive2.test',
            'password' => 'password123',
            'theme_color' => '#0d6efd',
        ], $posteDirecteur, $getService('Applications Web'));

        // 5. RESPONSABLE DEPARTEMENT - Département Recrutement (RH)
        $createUser([
            'name' => 'Nadia El Fassi',
            'email' => 'resp.recrutement@archive2.test',
            'password' => 'password123',
            'theme_color' => '#198754',
        ], $posteRespDep, $getService('Sourcing'));

        // 6. RESPONSABLE DEPARTEMENT - Département Développement (SI)
        $createUser([
            'name' => 'Thomas Dubois',
            'email' => 'resp.developpement@archive2.test',
            'password' => 'password123',
            'theme_color' => '#198754',
        ], $posteRespDep, $getService('Applications Web'));

        // 7. CHEF DE SERVICE - Service Paie (RH)
        $createUser([
            'name' => 'Awa Traoré',
            'email' => 'chef.paie@archive2.test',
            'password' => 'password123',
            'theme_color' => '#fd7e14',
        ], $posteChefService, $getService('Paie'));

        // 8. CHEF DE SERVICE - Service Helpdesk (SI)
        $createUser([
            'name' => 'Marc Leclerc',
            'email' => 'chef.helpdesk@archive2.test',
            'password' => 'password123',
            'theme_color' => '#fd7e14',
        ], $posteChefService, $getService('Helpdesk'));

        // 9. EMPLOYÉ - Service Paie (RH)
        $createUser([
            'name' => 'Fatou Diop',
            'email' => 'employe.paie@archive2.test',
            'password' => 'password123',
            'theme_color' => '#20c997',
        ], $posteEmploye, $getService('Paie'));

        // 10. EMPLOYÉ - Service Applications Web (SI)
        $createUser([
            'name' => 'Lucas Bernard',
            'email' => 'employe.devweb@archive2.test',
            'password' => 'password123',
            'theme_color' => '#20c997',
        ], $posteEmploye, $getService('Applications Web'));

        // 11. AGENT TEMPOORAIRE - Service Helpdesk (SI)
        $createUser([
            'name' => 'Ibrahim Sow',
            'email' => 'agent.helpdesk@archive2.test',
            'password' => 'password123',
            'theme_color' => '#6610f2',
        ], $posteAgent, $getService('Helpdesk'));

        // 12. AGENT TEMPOORAIRE - Service Accueil (DG)
        $createUser([
            'name' => 'Céline Moreau',
            'email' => 'agent.accueil@archive2.test',
            'password' => 'password123',
            'theme_color' => '#6610f2',
        ], $posteAgent, $getService('Accueil'));

        $this->command->info('✅ 12 utilisateurs de test créés avec succès !');
        $this->command->info('Mot de passe pour tous : password123');
    }
}