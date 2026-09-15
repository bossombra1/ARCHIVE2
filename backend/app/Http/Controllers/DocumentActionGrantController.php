<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentActionGrantStoreRequest;
use App\Models\Document;
use App\Models\DocumentActionGrant;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gère les droits d'action documentaire (modifier / supprimer / ajouter)
 * accordés par l'Administrateur Système à un ou plusieurs utilisateurs.
 *
 * Réservé à poste.level = 'admin' (middleware `poste:admin`, cf.
 * routes/api.php) — pas de Policy dédiée : contrairement à
 * DocumentPermission, il n'y a pas de notion de périmètre du grantor à
 * vérifier, c'est un droit exclusivement administrateur.
 */
class DocumentActionGrantController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | index
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $grants = DocumentActionGrant::where('company_id', $companyId)
            ->with(['user:id,name,email', 'grantedBy:id,name', 'documents:id,title'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DocumentActionGrant $g) => $this->formatGrant($g));

        return response()->json(['grants' => $grants]);
    }

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | Accorde les mêmes droits à un ou plusieurs utilisateurs en une seule
    | requête. Un utilisateur qui avait déjà un grant voit sa ligne mise à
    | jour (pas dupliquée — contrainte unique company_id+user_id).
    */
    public function store(DocumentActionGrantStoreRequest $request): JsonResponse
    {
        $admin = $request->user();
        $companyId = $admin->company_id;
        $validated = $request->validated();

        $scope = $validated['scope'];
        $documentIds = $scope === DocumentActionGrant::SCOPE_SPECIFIC
            ? ($validated['document_ids'] ?? [])
            : [];
        $expiresAt = $request->filled('expires_at') ? $request->date('expires_at') : null;

        $grants = DB::transaction(function () use ($validated, $companyId, $admin, $scope, $documentIds, $expiresAt) {
            $created = [];

            foreach ($validated['user_ids'] as $userId) {
                $grant = DocumentActionGrant::updateOrCreate(
                    ['company_id' => $companyId, 'user_id' => $userId],
                    [
                        'granted_by' => $admin->id,
                        'can_modify' => (bool) ($validated['can_modify'] ?? false),
                        'can_delete' => (bool) ($validated['can_delete'] ?? false),
                        'can_add' => (bool) ($validated['can_add'] ?? false),
                        'scope' => $scope,
                        'expires_at' => $expiresAt,
                    ]
                );

                $grant->documents()->sync($scope === DocumentActionGrant::SCOPE_SPECIFIC ? $documentIds : []);

                Journal::create([
                    'user_id' => $admin->id,
                    'action' => 'DOCUMENT_ACTION_GRANT_SET',
                    'description' => "Droits d'action documentaire mis à jour pour l'utilisateur #{$userId} "
                        . "(modifier=" . ($grant->can_modify ? '1' : '0')
                        . ", supprimer=" . ($grant->can_delete ? '1' : '0')
                        . ", ajouter=" . ($grant->can_add ? '1' : '0')
                        . ", portée={$scope}).",
                    'ip_address' => request()->ip(),
                ]);

                $created[] = $grant->fresh(['user:id,name,email', 'grantedBy:id,name', 'documents:id,title']);
            }

            return $created;
        });

        return response()->json([
            'message' => 'Droits accordés.',
            'grants' => collect($grants)->map(fn (DocumentActionGrant $g) => $this->formatGrant($g)),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | destroy
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $grant = DocumentActionGrant::where('company_id', $companyId)->find($id);

        if (! $grant) {
            return response()->json(['message' => 'Droit introuvable.'], 404);
        }

        DB::transaction(function () use ($grant, $request) {
            $userId = $grant->user_id;
            $grant->delete();

            Journal::create([
                'user_id' => $request->user()->id,
                'action' => 'DOCUMENT_ACTION_GRANT_REVOKE',
                'description' => "Révocation des droits d'action documentaire de l'utilisateur #{$userId}.",
                'ip_address' => $request->ip(),
            ]);
        });

        return response()->json(['message' => 'Droits révoqués.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function formatGrant(DocumentActionGrant $g): array
    {
        return [
            'id' => $g->id,
            'user' => $g->user ? ['id' => $g->user->id, 'name' => $g->user->name, 'email' => $g->user->email] : null,
            'granted_by' => $g->grantedBy ? ['id' => $g->grantedBy->id, 'name' => $g->grantedBy->name] : null,
            'can_modify' => $g->can_modify,
            'can_delete' => $g->can_delete,
            'can_add' => $g->can_add,
            'scope' => $g->scope,
            'documents' => $g->scope === DocumentActionGrant::SCOPE_SPECIFIC
                ? $g->documents->map(fn (Document $d) => ['id' => $d->id, 'title' => $d->title])
                : [],
            'expires_at' => $g->expires_at,
            'is_valid' => $g->isValid(),
            'is_permanent' => $g->isPermanent(),
            'is_expired' => $g->isExpired(),
            'created_at' => $g->created_at,
        ];
    }
}