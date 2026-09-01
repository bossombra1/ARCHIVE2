<?php

namespace App\Policies;

use App\Models\AmbiguousAffectationException;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;
use App\Services\DocumentVisibilityService;

/**
 * Policy centralisant les décisions d'autorisation sur les documents.
 *
 * Les méthodes reçoivent $user (l'utilisateur connecté) et délèuent à
 * DocumentVisibilityService. Toute AmbiguousAffectationException est
 * attrapée et transformée en refus (false).
 */
class DocumentPolicy
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

    public function viewAny(User $user): bool
    {
        return $this->safe(fn () => $this->visibility->canCreate($user));
    }

    public function view(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canView($user, $document));
    }

    public function create(User $user): bool
    {
        return $this->safe(fn () => $this->visibility->canCreate($user));
    }

    public function update(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canUpdate($user, $document));
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canDelete($user, $document));
    }

    public function download(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canDownload($user, $document));
    }

    public function grantPermission(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canGrantPermission($user, $document));
    }

    public function revokePermission(User $user, Document $document): bool
    {
        return $this->safe(fn () => $this->visibility->canRevokePermission($user, $document));
    }

    /**
     * Autorise la création d'une permission : vérifie le droit du grantor
     * sur le document ET la validité de la cible.
     */
    public function createPermission(User $user, Document $document, string $targetType, int $targetId): bool
    {
        return $this->safe(function () use ($user, $document, $targetType, $targetId) {
            if (! $this->visibility->canGrantPermission($user, $document)) {
                return false;
            }
            return $this->visibility->isValidPermissionTarget($user, $document, $targetType, $targetId);
        });
    }

    /**
     * Autorise la suppression d'une permission : vérifie le droit du grantor
     * sur le document parent de la permission.
     */
    public function deletePermission(User $user, DocumentPermission $permission): bool
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
