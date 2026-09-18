<?php

namespace App\Services;

use App\Models\AmbiguousAffectationException;
use App\Models\Affectation;
use App\Models\Document;
use App\Models\DocumentActionGrant;
use App\Models\DocumentPermission;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Centralise toute la logique de visibilité documentaire.
 *
 * Cette classe est la SEULE source de vérité pour :
 *   - construire la requête filtrée (index),
 *   - vérifier l'accès à un document précis (show, download),
 *   - vérifier le droit de création / modification / suppression,
 *   - vérifier le droit d'accorder / révoquer une permission.
 */
class DocumentVisibilityService
{
    public const LEVEL_ADMIN = 'admin';
    public const LEVEL_DG = 'dg';
    public const LEVEL_DIRECTEUR = 'directeur';
    public const LEVEL_RESP_DEP = 'responsable_departement';
    public const LEVEL_CHEF_SERVICE = 'chef_service';
    public const LEVEL_EMPLOYE = 'employe';
    public const LEVEL_AGENT = 'agent_temporaire';

    public const LEVELS_GRANTORS = [
        self::LEVEL_ADMIN,
        self::LEVEL_DG,
        self::LEVEL_DIRECTEUR,
        self::LEVEL_RESP_DEP,
        self::LEVEL_CHEF_SERVICE,
    ];

    /**
     * Construit la requête des documents visibles par $user.
     */
    public function scopeForUser(User $user): Builder
    {
        $affectation = $user->activeAffectation();
        if (! $affectation) {
            return Document::whereRaw('1=0');
        }

        $companyId = $user->company_id;
        $level = $affectation->poste->level ?? null;

        $base = Document::where('documents.company_id', $companyId);

        // Admin/DG : tous les documents de la company, pas de filtre supplémentaire.
        // On retourne directement $base pour éviter tout problème de closure vide.
        if ($level === self::LEVEL_ADMIN || $level === self::LEVEL_DG) {
            return $base;
        }

        $permittedDocIds = $this->permittedDocumentIdsForUser($user, $affectation);

        return $base->where(function (Builder $q) use ($affectation, $level, $permittedDocIds) {
            $this->applyNormalScope($q, $affectation, $level);
            if (! empty($permittedDocIds)) {
                $q->orWhereIn('documents.id', $permittedDocIds);
            }
        });
    }

    public function canView(User $user, Document $document): bool
    {
        if ($document->trashed()) {
            return false;
        }
        if ((int) $document->company_id !== (int) $user->company_id) {
            return false;
        }
                // Conflit d'affectations actives (anomalie de donnees) : une simple
        // verification de visibilite doit repondre false, jamais lever
        // d'exception (la journalisation reste faite par DocumentPolicy::safe
        // au niveau HTTP).
        try {
            $affectation = $user->activeAffectation();
        } catch (AmbiguousAffectationException $e) {
            return false;
        }
        if (! $affectation) {
            return false;
        }
        if ($this->inNormalScope($affectation, $affectation->poste->level ?? null, $document)) {
            return true;
        }
        return $this->hasValidPermission($user, $affectation, $document);
    }

    public function canDownload(User $user, Document $document): bool
    {
        return $this->canView($user, $document);
    }

   public function canCreate(User $user): bool
    {
        $affectation = $user->activeAffectation();
        if (! $affectation) {
            return false;
        }
        $level = $affectation->poste->level ?? null;

        // Postes hiérarchiques : droit de création par défaut, inchangé.
        if (in_array($level, [
            self::LEVEL_ADMIN,
            self::LEVEL_DG,
            self::LEVEL_DIRECTEUR,
            self::LEVEL_RESP_DEP,
            self::LEVEL_CHEF_SERVICE,
        ], true)) {
            return true;
        }

        // Employé / agent temporaire : uniquement si l'Administrateur
        // Système leur a explicitement accordé le droit d'ajouter (can_add).
        return $this->hasActionGrant($user, 'add');
    }

   public function canUpdate(User $user, Document $document): bool
    {
        if (! $this->canActOnDocumentBase($user, $document)) {
            return false;
        }
        return $this->hierarchicalModifyOrDelete($user, $document)
            || $this->hasActionGrant($user, 'modify', $document);
    }

    public function canDelete(User $user, Document $document): bool
    {
        if (! $this->canActOnDocumentBase($user, $document)) {
            return false;
        }
        return $this->hierarchicalModifyOrDelete($user, $document)
            || $this->hasActionGrant($user, 'delete', $document);
    }

    /**
     * Pré-conditions communes à modifier/supprimer : document non passé en
     * corbeille, même entreprise, affectation active. Si l'une échoue,
     * aucun droit (hiérarchique ou accordé par l'admin) ne peut s'appliquer.
     */
    private function canActOnDocumentBase(User $user, Document $document): bool
    {
        if ($document->trashed()) {
            return false;
        }
        if ((int) $document->company_id !== (int) $user->company_id) {
            return false;
        }
        return $user->activeAffectation() !== null;
    }

    /**
     * Droit hiérarchique "de base" (avant tout grant explicite), commun à
     * modifier et supprimer : employé/agent limités à leurs propres
     * documents, autres niveaux limités à leur périmètre organisationnel.
     */
    private function hierarchicalModifyOrDelete(User $user, Document $document): bool
    {
        $affectation = $user->activeAffectation();
        $level = $affectation->poste->level ?? null;

        if (in_array($level, [self::LEVEL_EMPLOYE, self::LEVEL_AGENT], true)) {
            return (int) $document->uploaded_by === (int) $user->id;
        }

        return $this->inNormalScope($affectation, $level, $document);
    }

    /**
     * Vrai si l'utilisateur possède un DocumentActionGrant valide couvrant
     * l'action demandée ('modify' | 'delete' | 'add'). Pour modify/delete,
     * vérifie en plus que le grant couvre le document (portée 'all', ou
     * document listé en portée 'specific'). 'add' est un droit global :
     * $document est ignoré pour cette action.
     */
    private function hasActionGrant(User $user, string $action, ?Document $document = null): bool
    {
        $grant = DocumentActionGrant::valid()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $grant) {
            return false;
        }

        $flag = match ($action) {
            'modify' => $grant->can_modify,
            'delete' => $grant->can_delete,
            'add' => $grant->can_add,
            default => false,
        };

        if (! $flag) {
            return false;
        }

        if ($action === 'add') {
            return true;
        }

        return $document !== null && $grant->coversDocument($document->id);
    }

    public function canGrantPermission(User $user, Document $document): bool
    {
        if ($document->trashed()) {
            return false;
        }
        if ((int) $document->company_id !== (int) $user->company_id) {
            return false;
        }
        $affectation = $user->activeAffectation();
        if (! $affectation) {
            return false;
        }
        $level = $affectation->poste->level ?? null;
        if (! in_array($level, self::LEVELS_GRANTORS, true)) {
            return false;
        }

        return $this->inNormalScope($affectation, $level, $document);
    }

    public function canRevokePermission(User $user, Document $document): bool
    {
        return $this->canGrantPermission($user, $document);
    }

    public function isValidPermissionTarget(User $grantor, Document $document, string $targetType, int $targetId): bool
    {
        if ((int) $document->company_id !== (int) $grantor->company_id) {
            return false;
        }
        $affectation = $grantor->activeAffectation();
        if (! $affectation) {
            return false;
        }
        $level = $affectation->poste->level ?? null;

        switch ($targetType) {
            case DocumentPermission::TARGET_USER:
                $targetUser = User::find($targetId);
                if (! $targetUser) {
                    return false;
                }
                if ((int) $targetUser->company_id !== (int) $grantor->company_id) {
                    return false;
                }
                if ($level === self::LEVEL_ADMIN || $level === self::LEVEL_DG) {
                    return true;
                }
                $targetAff = $targetUser->activeAffectation();
                if (! $targetAff) {
                    return false;
                }
                if ($level === self::LEVEL_DIRECTEUR) {
                    return $targetAff->direction_id !== null
                        && (int) $targetAff->direction_id === (int) $affectation->direction_id;
                }
                if ($level === self::LEVEL_RESP_DEP) {
                    return $targetAff->department_id !== null
                        && (int) $targetAff->department_id === (int) $affectation->department_id;
                }
                if ($level === self::LEVEL_CHEF_SERVICE) {
                    return (int) $targetAff->service_id === (int) $affectation->service_id;
                }
                return false;

            case DocumentPermission::TARGET_SERVICE:
                $targetService = \App\Models\Service::find($targetId);
                if (! $targetService) {
                    return false;
                }
                if ((int) $targetService->company_id !== (int) $grantor->company_id) {
                    return false;
                }
                if ($level === self::LEVEL_ADMIN || $level === self::LEVEL_DG) {
                    return true;
                }
                if ($level === self::LEVEL_DIRECTEUR) {
                    return $targetService->department
                        && $targetService->department->direction_id !== null
                        && (int) $targetService->department->direction_id === (int) $affectation->direction_id;
                }
                if ($level === self::LEVEL_RESP_DEP) {
                    return $targetService->department_id !== null
                        && (int) $targetService->department_id === (int) $affectation->department_id;
                }
                if ($level === self::LEVEL_CHEF_SERVICE) {
                    return (int) $targetService->id === (int) $affectation->service_id;
                }
                return false;

            case DocumentPermission::TARGET_POSTE:
                return \App\Models\Poste::where('id', $targetId)->exists();

            default:
                return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Implémentation interne
    |--------------------------------------------------------------------------
    */

    private function applyNormalScope(Builder $q, Affectation $affectation, ?string $level): void
    {
        switch ($level) {
            case self::LEVEL_DIRECTEUR:
                $directionId = $affectation->direction_id
                    ?? $affectation->service?->department?->direction_id;
                if (! $directionId) {
                    $q->whereRaw('1=0');
                    return;
                }
                $q->where(function (Builder $sub) use ($directionId) {
                    $sub->where('documents.direction_id', $directionId)
                        ->orWhereIn('documents.department_id', function ($qq) use ($directionId) {
                            $qq->select('id')->from('departments')->where('direction_id', $directionId);
                        })
                        ->orWhereIn('documents.service_id', function ($qq) use ($directionId) {
                            $qq->select('services.id')->from('services')
                                ->join('departments', 'services.department_id', '=', 'departments.id')
                                ->where('departments.direction_id', $directionId);
                        });
                });
                break;

            case self::LEVEL_RESP_DEP:
                $departmentId = $affectation->department_id
                    ?? $affectation->service?->department_id;
                if (! $departmentId) {
                    $q->whereRaw('1=0');
                    return;
                }
                $q->where(function (Builder $sub) use ($departmentId) {
                    $sub->where('documents.department_id', $departmentId)
                        ->orWhereIn('documents.service_id', function ($qq) use ($departmentId) {
                            $qq->select('id')->from('services')->where('department_id', $departmentId);
                        });
                });
                break;

            case self::LEVEL_CHEF_SERVICE:
            case self::LEVEL_EMPLOYE:
            case self::LEVEL_AGENT:
                $q->where('documents.service_id', $affectation->service_id);
                break;

            default:
                $q->whereRaw('1=0');
                break;
        }
    }

    private function inNormalScope(Affectation $affectation, ?string $level, Document $document): bool
    {
        switch ($level) {
            case self::LEVEL_ADMIN:
            case self::LEVEL_DG:
                return (int) $document->company_id === (int) $affectation->user->company_id;

            case self::LEVEL_DIRECTEUR:
                $directionId = $affectation->direction_id
                    ?? $affectation->service?->department?->direction_id;
                if (! $directionId) {
                    return false;
                }
                if ((int) $document->direction_id === (int) $directionId) {
                    return true;
                }
                if ($document->department_id) {
                    $dept = \App\Models\Department::find($document->department_id);
                    if ($dept && (int) $dept->direction_id === (int) $directionId) {
                        return true;
                    }
                }
                if ($document->service_id) {
                    $svc = \App\Models\Service::with('department')->find($document->service_id);
                    if ($svc && $svc->department
                        && (int) $svc->department->direction_id === (int) $directionId) {
                        return true;
                    }
                }
                return false;

            case self::LEVEL_RESP_DEP:
                $departmentId = $affectation->department_id
                    ?? $affectation->service?->department_id;
                if (! $departmentId) {
                    return false;
                }
                if ((int) $document->department_id === (int) $departmentId) {
                    return true;
                }
                if ($document->service_id) {
                    $svc = \App\Models\Service::find($document->service_id);
                    if ($svc && (int) $svc->department_id === (int) $departmentId) {
                        return true;
                    }
                }
                return false;

            case self::LEVEL_CHEF_SERVICE:
            case self::LEVEL_EMPLOYE:
            case self::LEVEL_AGENT:
                return (int) $document->service_id === (int) $affectation->service_id;

            default:
                return false;
        }
    }

    private function permittedDocumentIdsForUser(User $user, Affectation $affectation): array
    {
        $posteId = $affectation->poste_id;
        $serviceId = $affectation->service_id;
        $userId = $user->id;

        return DocumentPermission::valid()
            ->join('documents', 'documents.id', '=', 'document_permissions.document_id')
            ->where('documents.company_id', $user->company_id)
            ->whereNull('documents.deleted_at')
            ->where(function (Builder $q) use ($userId, $posteId, $serviceId) {
                $q->where(function (Builder $qq) use ($userId) {
                    $qq->where('target_type', DocumentPermission::TARGET_USER)
                        ->where('target_id', $userId);
                })->orWhere(function (Builder $qq) use ($posteId) {
                    $qq->where('target_type', DocumentPermission::TARGET_POSTE)
                        ->where('target_id', $posteId);
                })->orWhere(function (Builder $qq) use ($serviceId) {
                    $qq->where('target_type', DocumentPermission::TARGET_SERVICE)
                        ->where('target_id', $serviceId);
                });
            })
            ->pluck('document_permissions.document_id')
            ->unique()
            ->toArray();
    }

    private function hasValidPermission(User $user, Affectation $affectation, Document $document): bool
    {
        return DocumentPermission::valid()
            ->where('document_id', $document->id)
            ->where(function (Builder $q) use ($user, $affectation) {
                $q->where(function (Builder $qq) use ($user) {
                    $qq->where('target_type', DocumentPermission::TARGET_USER)
                        ->where('target_id', $user->id);
                })->orWhere(function (Builder $qq) use ($affectation) {
                    $qq->where('target_type', DocumentPermission::TARGET_POSTE)
                        ->where('target_id', $affectation->poste_id);
                })->orWhere(function (Builder $qq) use ($affectation) {
                    $qq->where('target_type', DocumentPermission::TARGET_SERVICE)
                        ->where('target_id', $affectation->service_id);
                });
            })
            ->exists();
    }
}