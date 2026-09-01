<?php

namespace Tests\Helpers;

use App\Models\Affectation;
use App\Models\Company;
use App\Models\Department;
use App\Models\Direction;
use App\Models\DocumentType;
use App\Models\Poste;
use App\Models\Service;
use App\Models\User;

/**
 * Helpers pour les tests documentaires.
 *
 * Centralise la création d'une organisation complète (company + direction +
 * département + service + postes) et d'utilisateurs avec une affectation
 * active sur un poste précis.
 */
trait DocumentTestHelper
{
    /**
     * Crée une organisation complète avec une company, 2 directions, chacune
     * avec 1 département contenant 2 services. Retourne un tableau structuré.
     */
    protected function createOrganization(): array
    {
        $company = Company::factory()->create();

        // Direction A (avec département + 2 services)
        $directionA = Direction::factory()->for($company)->create();
        $departmentA = Department::factory()->for($company)->for($directionA, 'direction')->create();
        $serviceA1 = Service::factory()->for($company)->for($departmentA, 'department')->create();
        $serviceA2 = Service::factory()->for($company)->for($departmentA, 'department')->create();

        // Direction B (autre direction, autre département, autre service)
        $directionB = Direction::factory()->for($company)->create();
        $departmentB = Department::factory()->for($company)->for($directionB, 'direction')->create();
        $serviceB1 = Service::factory()->for($company)->for($departmentB, 'department')->create();

        // Type de document
        $docType = DocumentType::factory()->for($company)->create();

        return [
            'company' => $company,
            'directionA' => $directionA,
            'directionB' => $directionB,
            'departmentA' => $departmentA,
            'departmentB' => $departmentB,
            'serviceA1' => $serviceA1,
            'serviceA2' => $serviceA2,
            'serviceB1' => $serviceB1,
            'docType' => $docType,
        ];
    }

    /**
     * Crée un utilisateur avec une affectation active sur un poste et service précis.
     *
     * @param string $level 'admin' | 'dg' | 'directeur' | 'responsable_departement' | 'chef_service' | 'employe' | 'agent_temporaire'
     */
    protected function createUserWithPoste(
        Company $company,
        string $level,
        ?Service $service = null,
        ?Department $department = null,
        ?Direction $direction = null,
    ): User {
        $user = User::factory()->for($company)->create();
        $poste = Poste::where('level', $level)->first();

        // Si service non fourni, on en crée un "au plus haut" : département et direction aussi.
        if (! $service) {
            $direction ??= Direction::factory()->for($company)->create();
            $department ??= Department::factory()->for($company)->for($direction, 'direction')->create();
            $service ??= Service::factory()->for($company)->for($department, 'department')->create();
        } else {
            // On remonte les parents depuis le service si possible
            $department ??= $service->department_id ? $service->department : null;
            $direction ??= $department && $department->direction_id ? $department->direction : null;
        }

        Affectation::factory()->for($user)->for($poste)->for($service)->create([
            'department_id' => $department?->id,
            'direction_id' => $direction?->id,
            'is_active' => true,
            'started_at' => today()->subMonth(),
            'ended_at' => null,
        ]);

        return $user->fresh();
    }
}
