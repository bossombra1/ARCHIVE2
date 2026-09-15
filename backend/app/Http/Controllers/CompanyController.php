<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gère les informations d'entreprise liées au forfait : consultation de
 * l'usage courant (tous les utilisateurs authentifiés) et changement de
 * taille/forfait (réservé à l'Administrateur Système, via poste:admin).
 */
class CompanyController extends Controller
{
    public function __construct(private readonly PlanLimitService $planLimit)
    {
    }

    /**
     * Usage courant du forfait (users/documents utilisés vs limites du
     * palier). Accessible à tout utilisateur authentifié de la company :
     * utile pour afficher un avertissement avant de bloquer une action
     * (ex. bouton "Ajouter" désactivé côté frontend).
     */
    public function planUsage(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json([
            'usage' => $this->planLimit->usageSummary($company),
        ]);
    }

    /**
     * Change la taille (= le forfait) de l'entreprise. Réservé à l'admin.
     * Un downgrade n'est jamais bloqué même si l'usage dépasse les
     * nouvelles limites : les créations resteront simplement bloquées tant
     * que l'usage n'est pas redescendu sous la limite (comportement
     * volontairement non destructif — aucune donnée n'est supprimée).
     */
    public function updateSize(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $validated = $request->validate([
            'size' => ['required', Rule::in(array_keys(config('plans.tiers', [])))],
        ]);

        $previousSize = $company->size;
        $company->update(['size' => $validated['size']]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'COMPANY_PLAN_UPDATE',
            'description' => "Changement de forfait : {$previousSize} -> {$validated['size']}.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Forfait mis à jour.',
            'usage' => $this->planLimit->usageSummary($company->fresh()),
        ]);
    }
}