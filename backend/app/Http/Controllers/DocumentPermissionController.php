<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentPermissionStoreRequest;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Journal;
use App\Services\DocumentVisibilityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentPermissionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly DocumentVisibilityService $visibility)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | index
    |--------------------------------------------------------------------------
    | Liste les permissions d'un document. Réservé aux utilisateurs qui
    | peuvent accorder des permissions sur ce document.
    */
    public function index(Request $request, int $documentId): JsonResponse
    {
        $user = $request->user();
        $document = Document::findOrFail($documentId);

        if (! $this->visibility->canGrantPermission($user, $document)) {
            return response()->json([
                'message' => "Vous n'êtes pas autorisé à consulter les permissions de ce document.",
            ], 403);
        }

        $permissions = $document->permissions()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DocumentPermission $p) => [
                'id' => $p->id,
                'document_id' => $p->document_id,
                'target_type' => $p->target_type,
                'target_id' => $p->target_id,
                'target_label' => $this->resolveTargetLabel($p),
                'expires_at' => $p->expires_at,
                'is_valid' => $p->isValid(),
                'is_permanent' => $p->isPermanent(),
                'is_expired' => $p->isExpired(),
                'created_at' => $p->created_at,
            ]);

        return response()->json([
            'permissions' => $permissions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | Crée une permission sur un document. Le grantor doit :
    *   - être dans un poste autorisé à accorder (admin/dg/directeur/
    *     responsable_departement/chef_service) ;
    *   - avoir accès au document dans son périmètre normal ;
    *   - ne pas cibler une cible hors de son propre périmètre.
    */
    public function store(DocumentPermissionStoreRequest $request, int $documentId): JsonResponse
    {
        $user = $request->user();
        $document = Document::findOrFail($documentId);

        $targetType = $request->input('target_type');
        $targetId = (int) $request->input('target_id');
        $expiresAt = $request->filled('expires_at') ? $request->date('expires_at') : null;

        // Vérifie le droit du grantor ET la validité de la cible dans son périmètre
        if (! $this->visibility->canGrantPermission($user, $document)) {
            return response()->json([
                'message' => "Vous n'êtes pas autorisé à accorder des permissions sur ce document.",
            ], 403);
        }
        if (! $this->visibility->isValidPermissionTarget($user, $document, $targetType, $targetId)) {
            return response()->json([
                'message' => "La cible sélectionnée n'est pas dans votre périmètre ou n'existe pas dans votre entreprise.",
            ], 422);
        }

        // Évite le doublon (mêmes document_id + target_type + target_id) :
        // si une permission existe déjà, on met juste à jour expires_at.
        $existing = DocumentPermission::where('document_id', $document->id)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();

        $permission = DB::transaction(function () use (
            $existing, $document, $targetType, $targetId, $expiresAt, $user
        ) {
            if ($existing) {
                $existing->expires_at = $expiresAt;
                $existing->save();
                $permission = $existing;
                $action = 'DOCUMENT_PERMISSION_UPDATE';
                $description = "Mise à jour de la permission #{$permission->id} sur le document #{$document->id}";
            } else {
                $permission = DocumentPermission::create([
                    'document_id' => $document->id,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'expires_at' => $expiresAt,
                ]);
                $action = 'DOCUMENT_PERMISSION_CREATE';
                $description = "Création de la permission #{$permission->id} (cible {$targetType}:{$targetId}) sur le document #{$document->id}";
            }

            Journal::create([
                'user_id' => $user->id,
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
            ]);

            return $permission;
        });

        return response()->json([
            'message' => 'Permission enregistrée.',
            'permission' => [
                'id' => $permission->id,
                'document_id' => $permission->document_id,
                'target_type' => $permission->target_type,
                'target_id' => $permission->target_id,
                'target_label' => $this->resolveTargetLabel($permission),
                'expires_at' => $permission->expires_at,
                'is_permanent' => $permission->isPermanent(),
                'is_valid' => $permission->isValid(),
            ],
        ], $existing ? 200 : 201);
    }

    /*
    |--------------------------------------------------------------------------
    | destroy
    |--------------------------------------------------------------------------
    | Supprime une permission. Le grantor doit pouvoir révoquer des
    * permissions sur le document parent.
    */
    public function destroy(Request $request, int $permissionId): JsonResponse
    {
        $user = $request->user();
        $permission = DocumentPermission::with('document')->find($permissionId);

        if (! $permission) {
            return response()->json(['message' => 'Permission introuvable.'], 404);
        }

        $document = $permission->document;
        if (! $document) {
            return response()->json(['message' => 'Document parent introuvable.'], 404);
        }

        if (! $this->visibility->canRevokePermission($user, $document)) {
            return response()->json([
                'message' => "Vous n'êtes pas autorisé à révoquer des permissions sur ce document.",
            ], 403);
        }

        DB::transaction(function () use ($permission, $user) {
            $permission->delete();
            Journal::create([
                'user_id' => $user->id,
                'action' => 'DOCUMENT_PERMISSION_DELETE',
                'description' => "Suppression de la permission #{$permission->id}",
                'ip_address' => request()->ip(),
            ]);
        });

        return response()->json(['message' => 'Permission révoquée.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Retourne un libellé lisible pour la cible d'une permission.
     */
    private function resolveTargetLabel(DocumentPermission $permission): ?string
    {
        return match ($permission->target_type) {
            DocumentPermission::TARGET_USER => optional(\App\Models\User::find($permission->target_id))->name,
            DocumentPermission::TARGET_POSTE => optional(\App\Models\Poste::find($permission->target_id))->name,
            DocumentPermission::TARGET_SERVICE => optional(\App\Models\Service::find($permission->target_id))->name,
            default => null,
        };
    }
}
