<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\User;

/**
 * Centralise la logique de forfait (limites liées à Company.size) :
 *   - lecture des limites du palier en cours (config/plans.php) ;
 *   - comptage de l'utilisation actuelle (utilisateurs actifs, documents
 *     actifs hors corbeille) ;
 *   - vérification qu'une limite est atteinte, avant création.
 *
 * Un utilisateur "actif" = status = true (un compte désactivé libère sa
 * place dans le forfait). Un document "actif" = non passé en corbeille
 * (soft delete), cohérent avec le module Corbeille (TrashController).
 */
class PlanLimitService
{
    /**
     * Limites (label, max_users, max_documents) du palier de l'entreprise.
     * Repli sur le palier 'small' si la taille est inconnue/non migrée.
     */
    public function tierFor(Company $company): array
    {
        $tiers = config('plans.tiers', []);

        return $tiers[$company->size] ?? $tiers['small'] ?? [
            'label' => $company->size,
            'max_users' => null,
            'max_documents' => null,
        ];
    }

    public function activeUsersCount(Company $company): int
    {
        return User::where('company_id', $company->id)
            ->where('status', true)
            ->count();
    }

    public function activeDocumentsCount(Company $company): int
    {
        // SoftDeletes exclut déjà automatiquement les documents en corbeille.
        return Document::where('company_id', $company->id)->count();
    }

    public function maxUsers(Company $company): ?int
    {
        return $this->tierFor($company)['max_users'] ?? null;
    }

    public function maxDocuments(Company $company): ?int
    {
        return $this->tierFor($company)['max_documents'] ?? null;
    }

    /** Vrai si l'entreprise peut encore ajouter un utilisateur (null = illimité). */
    public function canAddUser(Company $company): bool
    {
        $max = $this->maxUsers($company);
        return $max === null || $this->activeUsersCount($company) < $max;
    }

    /** Vrai si l'entreprise peut encore ajouter un document (null = illimité). */
    public function canAddDocument(Company $company): bool
    {
        $max = $this->maxDocuments($company);
        return $max === null || $this->activeDocumentsCount($company) < $max;
    }

    public function usersRemaining(Company $company): ?int
    {
        $max = $this->maxUsers($company);
        return $max === null ? null : max(0, $max - $this->activeUsersCount($company));
    }

    public function documentsRemaining(Company $company): ?int
    {
        $max = $this->maxDocuments($company);
        return $max === null ? null : max(0, $max - $this->activeDocumentsCount($company));
    }

    /**
     * Résumé complet de l'usage du forfait, pour l'API
     * (cf. CompanyController::planUsage au lot 3).
     */
    public function usageSummary(Company $company): array
    {
        $tier = $this->tierFor($company);

        return [
            'size' => $company->size,
            'label' => $tier['label'] ?? $company->size,
            'users' => [
                'used' => $this->activeUsersCount($company),
                'max' => $tier['max_users'] ?? null,
                'remaining' => $this->usersRemaining($company),
                'limit_reached' => ! $this->canAddUser($company),
            ],
            'documents' => [
                'used' => $this->activeDocumentsCount($company),
                'max' => $tier['max_documents'] ?? null,
                'remaining' => $this->documentsRemaining($company),
                'limit_reached' => ! $this->canAddDocument($company),
            ],
        ];
    }
}