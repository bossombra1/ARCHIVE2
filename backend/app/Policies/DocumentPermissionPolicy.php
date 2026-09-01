<?php

namespace App\Policies;

use App\Models\AmbiguousAffectationException;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;
use App\Services\DocumentVisibilityService;

/**
 * Policy pour la gestion des permissions documentaires.
 *
 * Comme DocumentPermission est lié à un document, on délègue à la policy
 * du document parent pour toute décision. La permission ne vit qu'en tant
 * qu'enfant d'un document.
 */
class DocumentPermissionPolicy
{
    public function __construct(private readonly DocumentVisibilityService $visibility)
    {
    }

    private function safe(callable $callback): bool
    {
        try {
            return $callback();
        } catch (AmbiguousAffectationException $e) {
            \App\Models\Journal::create([
                'user_id' => $e->userId,
                'action' => 'AMBIGUOUS_AFFECTATION',
                'description' => "Refus d'accès documentaire : affectation ambiguë détectée.",
                'ip_address' => request()?->ip(),
            ]);
            return false;
        }
    }

    public function viewAny(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canGrantPermission($user, $document));
    }

    public function create(User $user, Document $document, string $targetType, int $targetId): bool
    {
        return $this->safe(function () use ($user, $document, $targetType, $targetId) {
            if (! $this->visibility->canGrantPermission($user, $document)) {
                return false;
            }
            return $this->visibility->isValidPermissionTarget($user, $document, $targetType, $targetId);
        });
    }

    public function delete(User $user, DocumentPermission $permission): bool
    {
        return $this->safe(function () use ($user, $permission) {
            $document = $permission->document;
            if (! $document) {
                return false;
            }
            return $this->visibility->canRevokePermission($user, $document);
        });
    }
}
