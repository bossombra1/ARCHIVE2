<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\PlanChangeRequest;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Gère les informations d'entreprise liées au forfait.
 *
 * L'Administrateur Système de l'entreprise ne peut plus changer le forfait
 * librement : il soumet une DEMANDE (plan_change_requests) qui reste en
 * attente jusqu'à validation MANUELLE hors application (en base ou via
 * `php artisan plan:process`). Une fois approuvée, seule l'application
 * (bouton "Appliquer" de l'admin) bascule le palier, en journalisant.
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
     * Dernière demande de changement de forfait visible (pending, approved
     * ou rejected — pas les demandes déjà appliquées), ou null. Sert à
     * afficher l'état dans l'UI : en attente, approuvée (-> "Appliquer"),
     * ou refusée hors application.
     */
    public function currentPlanChangeRequest(Request $request): JsonResponse
    {
        $requestModel = PlanChangeRequest::latestVisibleFor($request->user()->company);

        return response()->json([
            'request' => $requestModel ? $requestModel->load('requestedBy:id,name') : null,
        ]);
    }

    /**
     * L'admin soumet une demande de changement de forfait. La demande reste
     * "pending" : elle n'a AUCUN effet sur le palier tant qu'elle n'est pas
     * validée manuellement hors application.
     */
    public function requestPlanChange(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $validated = $request->validate([
            'requested_size' => ['required', Rule::in(array_keys(config('plans.tiers', [])))],
        ]);

        if ($validated['requested_size'] === $company->size) {
            return response()->json([
                'message' => "Ce palier est déjà le forfait actuel de l'entreprise.",
            ], 422);
        }

        $open = PlanChangeRequest::openFor($company);
        if ($open) {
            return response()->json([
                'message' => $open->isPending()
                    ? 'Une demande est déjà en attente de validation externe.'
                    : 'Une demande a déjà été approuvée et attend d\'être appliquée.',
                'request' => $open,
            ], 409);
        }

        $planRequest = DB::transaction(function () use ($company, $request, $validated) {
            $planRequest = PlanChangeRequest::create([
                'company_id' => $company->id,
                'requested_by' => $request->user()->id,
                'current_size' => $company->size,
                'requested_size' => $validated['requested_size'],
                'status' => PlanChangeRequest::STATUS_PENDING,
            ]);

            Journal::create([
                'user_id' => $request->user()->id,
                'action' => 'PLAN_CHANGE_REQUESTED',
                'description' => "Demande de changement de forfait : {$company->size} -> {$validated['requested_size']} (demande #{$planRequest->id}).",
                'ip_address' => $request->ip(),
            ]);

            return $planRequest;
        });

        return response()->json([
            'message' => 'Demande de changement de forfait enregistrée. Elle restera en attente jusqu\'à sa validation externe.',
            'request' => $planRequest->load('requestedBy:id,name'),
        ], 201);
    }

    /**
     * Applique une demande APPROUVÉE manuellement hors application.
     * Impossible si la demande est encore pending (409) : l'application ne
     * peut pas se valider elle-même. Un downgrade n'est jamais bloqué même
     * si l'usage dépasse les nouvelles limites : les créations resteront
     * simplement bloquées tant que l'usage n'est pas redescendu sous la
     * limite (comportement non destructif — aucune donnée supprimée).
     */
    public function applyPlanChangeRequest(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $planRequest = PlanChangeRequest::openFor($company);

        if (! $planRequest || ! $planRequest->isApproved()) {
            return response()->json([
                'message' => 'Aucune demande approuvée à appliquer.',
                'request' => $planRequest,
            ], 409);
        }

        $previousSize = $company->size;

        DB::transaction(function () use ($company, $request, $planRequest, $previousSize) {
            $company->update(['size' => $planRequest->requested_size]);
            $planRequest->update(['status' => PlanChangeRequest::STATUS_APPLIED]);

            Journal::create([
                'user_id' => $request->user()->id,
                'action' => 'PLAN_CHANGE_APPLIED',
                'description' => "Application de la demande #{$planRequest->id} : changement de forfait {$previousSize} -> {$planRequest->requested_size}.",
                'ip_address' => $request->ip(),
            ]);
        });

        return response()->json([
            'message' => 'Forfait mis à jour.',
            'request' => $planRequest->fresh(),
            'usage' => $this->planLimit->usageSummary($company->fresh()),
        ]);
    }
}
