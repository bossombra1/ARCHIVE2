<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Document;
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
        $affectation = $user->activeAffectation();
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
        return in_array($affectation->poste->level ?? null, [
            self::LEVEL_ADMIN,
            self::LEVEL_DG,
            self::LEVEL_DIRECTEUR,
            self::LEVEL_RESP_DEP,
            self::LEVEL_CHEF_SERVICE,
            self::LEVEL_EMPLOYE,
            self::LEVEL_AGENT,
        ], true);
    }

    public function canUpdate(User $user, Document $document): bool
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

        if (in_array($level, [self::LEVEL_EMPLOYE, self::LEVEL_AGENT], true)) {
            return (int) $document->uploaded_by === (int) $user->id;
        }

        return $this->inNormalScope($affectation, $level, $document);
    }

    public function canDelete(User $user, Document $document): bool
    {
        return $this->canUpdate($user, $document);
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