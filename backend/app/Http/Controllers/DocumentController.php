<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentStoreRequest;
use App\Http\Requests\DocumentUpdateRequest;
use App\Models\AmbiguousAffectationException;
use App\Models\Document;
use App\Models\Journal;
use App\Services\DocumentVisibilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DocumentVisibilityService $visibility,
        private readonly \App\Services\PlanLimitService $planLimit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | index
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeForIndex($user);

        $query = $this->visibility->scopeForUser($user)
            ->with(['documentType:id,name', 'uploader:id,name', 'service:id,name', 'department:id,name', 'direction:id,name'])
            ->select([
                'documents.id', 'documents.company_id', 'documents.document_type_id',
                'documents.uploaded_by', 'documents.service_id', 'documents.department_id',
                'documents.direction_id', 'documents.title', 'documents.description',
                'documents.file_type', 'documents.file_size', 'documents.created_at',
                'documents.updated_at',
            ]);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('documents.title', 'like', "%{$search}%");
        }
        if ($typeId = $request->integer('document_type_id')) {
            $query->where('documents.document_type_id', $typeId);
        }

        $perPage = min(50, max(5, $request->integer('per_page', 15)));
        $documents = $query->orderByDesc('documents.created_at')->paginate($perPage);

        // Droits calculés par document : c'est la SEULE façon fiable pour le
        // frontend de savoir s'il doit afficher Modifier/Supprimer/Accorder
        // des permissions — jamais déduit du seul poste.level côté client.
        $documents->getCollection()->transform(function (Document $document) use ($user) {
            $document->can_update = $this->visibility->canUpdate($user, $document);
            $document->can_delete = $this->visibility->canDelete($user, $document);
            $document->can_grant_permission = $this->visibility->canGrantPermission($user, $document);
            return $document;
        });

        return response()->json($documents);
    }

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    */
    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('create', Document::class);

        if (! $this->planLimit->canAddDocument($user->company)) {
            return response()->json([
                'error' => 'PLAN_DOCUMENT_LIMIT_REACHED',
                'message' => "Limite de documents de votre forfait atteinte. Contactez l'Administrateur Système pour passer à un forfait supérieur.",
            ], 403);
        }

        $affectation = $user->activeAffectation();
        if (! $affectation) {
            return $this->noAffectationResponse();
        }

        $companyId = $user->company_id;
        $serviceId = $affectation->service_id;
        $departmentId = $affectation->department_id ?? $affectation->service?->department_id;
        $directionId = $affectation->direction_id ?? $affectation->service?->department?->direction_id;

        $file = $request->file('file');
        $validated = $request->validated();

        $detectedMime = $this->detectRealMime($file);
        if (! $detectedMime) {
            return response()->json([
                'message' => "Le type de fichier n'a pas pu être déterminé ou n'est pas autorisé.",
            ], 422);
        }
        $extension = config('documents.allowed_mimes.' . $detectedMime);
        if (! $extension) {
            return response()->json([
                'message' => "Le type de fichier n'est pas autorisé.",
            ], 422);
        }

        $storedPath = $this->storeFileSecurely($file, $extension, $companyId);

        try {
            $document = DB::transaction(function () use (
                $validated, $file, $user, $companyId, $serviceId, $departmentId, $directionId, $storedPath, $extension
            ) {
                $document = Document::create([
                    'company_id' => $companyId,
                    'document_type_id' => $validated['document_type_id'],
                    'uploaded_by' => $user->id,
                    'service_id' => $serviceId,
                    'department_id' => $departmentId,
                    'direction_id' => $directionId,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'file_path' => $storedPath,
                    'file_type' => $extension,
                    'file_size' => $file->getSize(),
                ]);

                Journal::create([
                    'user_id' => $user->id,
                    'action' => 'DOCUMENT_CREATE',
                    'description' => "Création du document #{$document->id} : {$document->title}",
                    'ip_address' => request()->ip(),
                ]);

                return $document;
            });
        } catch (\Throwable $e) {
            Storage::disk(config('documents.disk', 'local'))->delete($storedPath);
            throw $e;
        }

        $document->load(['documentType:id,name', 'uploader:id,name', 'service:id,name', 'department:id,name', 'direction:id,name']);

        return response()->json([
            'message' => 'Document créé avec succès.',
            'document' => $document,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | show
    |--------------------------------------------------------------------------
    */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $document = Document::with(['documentType', 'uploader:id,name,email', 'service:id,name', 'department:id,name', 'direction:id,name'])
            ->find($id);

        if (! $document) {
            return $this->notFoundResponse();
        }

        if (! $this->visibility->canView($user, $document)) {
            return $this->notFoundResponse();
        }

       $data = $document->toArray();
        unset($data['file_path']);
        $data['can_update'] = $this->visibility->canUpdate($user, $document);
        $data['can_delete'] = $this->visibility->canDelete($user, $document);
        $data['can_grant_permission'] = $this->visibility->canGrantPermission($user, $document);
        return response()->json(['document' => $data]);
    }

    /*
    |--------------------------------------------------------------------------
    | download
    |--------------------------------------------------------------------------
    | Le paramètre ?inline=1 force l'affichage inline (prévisualisation dans
    | le navigateur) au lieu du téléchargement. Sans paramètre, c'est un
    | téléchargement classique.
    */
    public function download(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $document = Document::find($id);

        if (! $document) {
            return $this->notFoundResponse();
        }

        if (! $this->visibility->canDownload($user, $document)) {
            return $this->notFoundResponse();
        }

        $disk = Storage::disk(config('documents.disk', 'local'));
        if (! $disk->exists($document->file_path)) {
            Journal::create([
                'user_id' => $user->id,
                'action' => 'DOCUMENT_FILE_MISSING',
                'description' => "Fichier introuvable pour le document #{$document->id}",
                'ip_address' => request()->ip(),
            ]);
            return $this->notFoundResponse();
        }

        Journal::create([
            'user_id' => $user->id,
            'action' => 'DOCUMENT_DOWNLOAD',
            'description' => "Téléchargement du document #{$document->id}",
            'ip_address' => request()->ip(),
        ]);

        $downloadName = $this->buildSafeDownloadName($document);

        // Mode inline (prévisualisation) : Content-Disposition: inline
        // Le navigateur affiche le PDF/image directement sans télécharger.
        $inline = $request->boolean('inline', false);
        $disposition = $inline ? 'inline' : 'attachment';

        $mimeType = $this->mimeTypeFor($document->file_type);

        return $disk->response($document->file_path, $downloadName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $downloadName . '"',
            'Cache-Control' => 'private, max-age=0, no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */
    public function update(DocumentUpdateRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $document = Document::find($id);

        if (! $document) {
            return $this->notFoundResponse();
        }

        if (! $this->visibility->canUpdate($user, $document)) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validated();
        $document->fill($validated);

        if ($document->isDirty()) {
            DB::transaction(function () use ($document, $user) {
                $document->save();
                Journal::create([
                    'user_id' => $user->id,
                    'action' => 'DOCUMENT_UPDATE',
                    'description' => "Mise à jour du document #{$document->id}",
                    'ip_address' => request()->ip(),
                ]);
            });
        }

        return response()->json([
            'message' => 'Document mis à jour.',
            'document' => $document->fresh(['documentType:id,name', 'uploader:id,name', 'service:id,name', 'department:id,name', 'direction:id,name']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | destroy (soft delete)
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $document = Document::find($id);

        if (! $document) {
            return $this->notFoundResponse();
        }

        if (! $this->visibility->canDelete($user, $document)) {
            return $this->forbiddenResponse();
        }

        DB::transaction(function () use ($document, $user) {
            $document->delete();
            Journal::create([
                'user_id' => $user->id,
                'action' => 'DOCUMENT_DELETE',
                'description' => "Suppression (soft) du document #{$document->id} : {$document->title}",
                'ip_address' => request()->ip(),
            ]);
        });

        return response()->json(['message' => 'Document supprimé.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privés
    |--------------------------------------------------------------------------
    */

    private function authorizeForIndex($user): void
    {
        if (! $user) {
            throw new AuthenticationException();
        }

        // Lister les documents n'exige PAS le droit de creation : un employe/agent
        // voit les documents de son service sans droit d'ajout (spec. 20
        // scenarios 6-7). Le filtrage reste assure par scopeForUser, et l'upload
        // reste verrouille separement (Policy create + Droits d'action documentaire).
        // Conflit d'affectations actives = anomalie de donnees -> refus 403 explicite.
        try {
            $hasActiveAffectation = $user->activeAffectation() !== null;
        } catch (AmbiguousAffectationException $e) {
            throw new AuthorizationException(
                "Plusieurs affectations actives detectees. Veuillez contacter l'administrateur."
            );
        }

        if (! $hasActiveAffectation) {
            throw new AuthorizationException('Aucune affectation active ou compte non autorise.');
        }
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json(['message' => 'Document introuvable.'], 404);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'message' => "Vous n'êtes pas autorisé à effectuer cette action.",
        ], 403);
    }

    private function noAffectationResponse(): JsonResponse
    {
        return response()->json([
            'message' => "Aucune affectation active trouvée pour votre compte.",
        ], 403);
    }

    private function detectRealMime(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        $allowed = config('documents.allowed_mimes', []);
        return array_key_exists($mime, $allowed) ? $mime : null;
    }

    private function storeFileSecurely(UploadedFile $file, string $extension, int $companyId): string
    {
        $diskName = config('documents.disk', 'local');
        $disk = Storage::disk($diskName);

        $filename = Str::uuid()->toString() . '.' . $extension;
        $directory = sprintf('documents/%d/%s/%s', $companyId, now()->format('Y'), now()->format('m'));

        $disk->makeDirectory($directory);

        $file->storeAs($directory, $filename, $diskName);

        return $directory . '/' . $filename;
    }

    private function buildSafeDownloadName(Document $document): string
    {
        $base = Str::slug($document->title ?: 'document');
        $extension = $document->file_type ?: 'bin';
        return $base . '.' . $extension;
    }

    /**
     * Retourne le type MIME standard pour une extension donnée.
     * Utilisé pour le Content-Type de la réponse de téléchargement.
     */
    private function mimeTypeFor(string $fileType): string
    {
        return match ($fileType) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}