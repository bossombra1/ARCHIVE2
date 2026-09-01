<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Direction;
use App\Models\DocumentType;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        // 1. DIRECTIONS
        $directions = [
            'Direction Générale',
            'Direction des Ressources Humaines',
            'Direction Financière et Comptable',
            'Direction des Systèmes d\'Information',
            'Direction Opérations',
        ];
        $directionIds = [];
        foreach ($directions as $name) {
            $d = Direction::create([
                'company_id' => $companyId,
                'name' => $name,
            ]);
            $directionIds[$name] = $d->id;
        }

        // 2. DÉPARTEMENTS
        $departments = [
            ['name' => 'Secrétariat Général', 'direction' => 'Direction Générale'],
            ['name' => 'Communication', 'direction' => 'Direction Générale'],
            ['name' => 'Recrutement', 'direction' => 'Direction des Ressources Humaines'],
            ['name' => 'Gestion du Personnel', 'direction' => 'Direction des Ressources Humaines'],
            ['name' => 'Formation', 'direction' => 'Direction des Ressources Humaines'],
            ['name' => 'Comptabilité', 'direction' => 'Direction Financière et Comptable'],
            ['name' => 'Trésorerie', 'direction' => 'Direction Financière et Comptable'],
            ['name' => 'Développement', 'direction' => 'Direction des Systèmes d\'Information'],
            ['name' => 'Infrastructure', 'direction' => 'Direction des Systèmes d\'Information'],
            ['name' => 'Support Utilisateurs', 'direction' => 'Direction des Systèmes d\'Information'],
            ['name' => 'Logistique', 'direction' => 'Direction Opérations'],
            ['name' => 'Production', 'direction' => 'Direction Opérations'],
        ];
        $deptIds = [];
        foreach ($departments as $dept) {
            $d = Department::create([
                'company_id' => $companyId,
                'direction_id' => $directionIds[$dept['direction']],
                'name' => $dept['name'],
            ]);
            $deptIds[$dept['name']] = $d->id;
        }

        // 3. SERVICES
        $services = [
            ['name' => 'Accueil', 'dept' => 'Secrétariat Général'],
            ['name' => 'Relations Presse', 'dept' => 'Communication'],
            ['name' => 'Événementiel', 'dept' => 'Communication'],
            ['name' => 'Sourcing', 'dept' => 'Recrutement'],
            ['name' => 'Entretiens', 'dept' => 'Recrutement'],
            ['name' => 'Paie', 'dept' => 'Gestion du Personnel'],
            ['name' => 'Contrats', 'dept' => 'Gestion du Personnel'],
            ['name' => 'Plan de Formation', 'dept' => 'Formation'],
            ['name' => 'Fournisseurs', 'dept' => 'Comptabilité'],
            ['name' => 'Clients', 'dept' => 'Comptabilité'],
            ['name' => 'Banques', 'dept' => 'Trésorerie'],
            ['name' => 'Applications Web', 'dept' => 'Développement'],
            ['name' => 'Applications Mobiles', 'dept' => 'Développement'],
            ['name' => 'Réseau', 'dept' => 'Infrastructure'],
            ['name' => 'Helpdesk', 'dept' => 'Support Utilisateurs'],
            ['name' => 'Achats', 'dept' => 'Logistique'],
            ['name' => 'Atelier', 'dept' => 'Production'],
        ];
        foreach ($services as $svc) {
            Service::create([
                'company_id' => $companyId,
                'department_id' => $deptIds[$svc['dept']],
                'name' => $svc['name'],
            ]);
        }

        // 4. TYPES DE DOCUMENTS supplémentaires
        $types = [
            'Demande de congé',
            'Bulletin de paie',
            'Bon de commande',
            'Reçu',
            'Procédure',
            'Manuel',
            'CV',
            'Lettre de motivation',
        ];
        foreach ($types as $name) {
            DocumentType::firstOrCreate([
                'company_id' => $companyId,
                'name' => $name,
            ]);
        }
    }
}