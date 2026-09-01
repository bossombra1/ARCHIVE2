<?php

namespace App\Http\Controllers;

use App\Models\AmbiguousAffectationException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Service;
use App\Models\User;
use App\Services\DocumentVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private readonly DocumentVisibilityService $visibility)
    {
    }

    /**
     * Statistiques du dashboard.
     *
     * Renvoie toujours un 200 avec des compteurs (zéro en cas d'erreur),
     * jamais de 500, pour éviter de planter le frontend.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->emptyStats();
            }

            $companyId = $user->company_id;

            // Compteurs organisationnels : toujours au niveau company.
            $totalDocumentTypes = DocumentType::where('company_id', $companyId)->count();
            $totalServices = Service::where('company_id', $companyId)->count();
            $totalUsers = User::where('company_id', $companyId)->count();

            // Compteurs documentaires : filtrés par périmètre.
            $totalDocuments = 0;
            $documentsByType = collect();

            try {
                $scopedQuery = $this->visibility->scopeForUser($user);
                $scopedDocumentsQuery = (clone $scopedQuery);

                $totalDocuments = $scopedQuery->count();

                $visibleDocIds = $scopedDocumentsQuery->pluck('documents.id')->all();

                if (! empty($visibleDocIds)) {
                    $documentsByType = DocumentType::where('company_id', $companyId)
                        ->with(['documents' => function ($q) use ($visibleDocIds) {
                            $q->whereIn('id', $visibleDocIds);
                        }])
                        ->get()
                        ->map(fn ($type) => [
                            'name' => $type->name,
                            'count' => $type->documents->count(),
                        ])
                        ->values();
                } else {
                    // Aucun document visible -> répartition vide avec noms des types
                    $documentsByType = DocumentType::where('company_id', $companyId)
                        ->get()
                        ->map(fn ($type) => [
                            'name' => $type->name,
                            'count' => 0,
                        ])
                        ->values();
                }
            } catch (AmbiguousAffectationException $e) {
                Log::warning('Dashboard stats - AmbiguousAffectation', [
                    'user_id' => $e->userId,
                    'count' => $e->count,
                ]);
                $totalDocuments = 0;
                $documentsByType = collect();
            } catch (\Throwable $e) {
                Log::error('Dashboard stats - Erreur de visibilité documentaire', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $totalDocuments = 0;
                $documentsByType = collect();
            }

            return response()->json([
                'total_documents' => $totalDocuments,
                'total_document_types' => $totalDocumentTypes,
                'total_services' => $totalServices,
                'total_users' => $totalUsers,
                'documents_by_type' => $documentsByType,
            ]);
        } catch (\Throwable $e) {
            Log::error('Dashboard stats - Erreur fatale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->emptyStats();
        }
    }

    private function emptyStats(): JsonResponse
    {
        return response()->json([
            'total_documents' => 0,
            'total_document_types' => 0,
            'total_services' => 0,
            'total_users' => 0,
            'documents_by_type' => [],
        ]);
    }
}