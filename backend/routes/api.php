<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DirectionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentPermissionController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PosteController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use App\Models\Department;
use App\Models\Direction;
use App\Models\Poste;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// 1. ROUTES PUBLIQUES (Setup & Auth)
// ==========================================

Route::get('/setup/check', [SetupController::class, 'check']);
Route::post('/setup/store', [SetupController::class, 'storeSetup']);
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// 2. ROUTES PROTÉGÉES (Sanctum + Entreprise configurée)
// ==========================================
Route::middleware(['auth:sanctum', 'company.configured'])->group(function () {

    // ---- Auth & profil ----
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        $user = $request->user();
        $affectationActive = $user->affectations()
            ->where('is_active', true)
            ->with(['poste:id,name,level', 'service:id,company_id,department_id,name', 'department:id,company_id,direction_id,name', 'direction:id,company_id,name'])
            ->first();
        $userData = $user->toArray();
        $userData['affectation'] = $affectationActive;
        $userData['company'] = $user->company;
        return response()->json([
            'user' => $userData,
            'affectation' => $affectationActive,
        ]);
    });

    Route::get('/user/current-affectation', function (Request $request) {
        $user = $request->user();
        $affectation = $user->affectations()->where('is_active', 1)
            ->with('poste:id,name,level')->first();
        return response()->json([
            'poste' => $affectation ? $affectation->poste : null
        ]);
    });

    // ---- Dashboard ----
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ==========================================
    // 3. ROUTES ACCESSIBLES À TOUS LES USERS AUTHENTIFIÉS
    // ==========================================
    // Ces routes servent à alimenter les filtres, formulaires d'upload
    // et la gestion des permissions documentaires. Elles ne retournent
    // QUE les données de la company de l'utilisateur.

    // Liste des types de documents (pour filtres + form upload)
    Route::get('/document-types', [DocumentTypeController::class, 'index']);

    // Liste des services (pour filtres + permissions modal)
    Route::get('/services', [ServiceController::class, 'index']);

    // Liste des postes (pour permissions modal)
    Route::get('/postes', [PosteController::class, 'index']);
    Route::get('/postes/{poste}', [PosteController::class, 'show']);

    // Liste minimale des users (pour permissions modal cible user)
    Route::get('/users/minimal', function (Request $request) {
        return response()->json(
            User::where('company_id', $request->user()->company_id)
                ->select('id', 'name', 'email')->orderBy('name')->get()
        );
    });

    // Listes minimales directions/départements (utilisées par certains formulaires
    // non-admin, ex. affichage de la hiérarchie dans les filtres)
    Route::get('/directions/minimal', function (Request $request) {
        return response()->json(
            Direction::where('company_id', $request->user()->company_id)
                ->select('id', 'name')->orderBy('name')->get()
        );
    });

    Route::get('/departments/minimal', function (Request $request) {
        return response()->json(
            Department::where('company_id', $request->user()->company_id)
                ->select('id', 'name', 'direction_id')->orderBy('name')->get()
        );
    });

    // ==========================================
    // 4. DOCUMENTS (cœur GED) — sécurité par Policy
    // ==========================================
    // La visibilité des documents est gérée par DocumentVisibilityService
    // (via DocumentPolicy), pas par un middleware poste:.

    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::apiResource('documents', DocumentController::class);

    // Permissions documentaires : la Policy vérifie que l'utilisateur est
    // grantor sur le document concerné (admin/dg/directeur/resp_dep/chef_service
    // dans leur périmètre). Pas besoin de middleware poste: ici.
    Route::get('/documents/{document}/permissions', [DocumentPermissionController::class, 'index'])
        ->name('documents.permissions.index');
    Route::post('/documents/{document}/permissions', [DocumentPermissionController::class, 'store'])
        ->name('documents.permissions.store');
    Route::delete('/document-permissions/{permission}', [DocumentPermissionController::class, 'destroy'])
        ->name('document-permissions.destroy');

    // ==========================================
    // 5. ROUTES RÉSERVÉES À L'ADMINISTRATEUR SYSTÈME
    // ==========================================
    // Toutes les routes de gestion (CRUD sur la structure organisationnelle,
    // les users, les journaux) sont réservées à l'admin. Un user non-admin
    // qui tente d'appeler ces routes directement (via Postman, curl, etc.)
    // recevra un 403 UNAUTHORIZED_POSTE.
    Route::middleware('poste:admin')->group(function () {

        // ---- Types de documents (CRUD complet) ----
        Route::post('/document-types', [DocumentTypeController::class, 'store']);
        Route::put('/document-types/{id}', [DocumentTypeController::class, 'update']);
        Route::patch('/document-types/{id}', [DocumentTypeController::class, 'update']);
        Route::delete('/document-types/{id}', [DocumentTypeController::class, 'destroy']);

        // ---- Services (CRUD complet) ----
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{id}', [ServiceController::class, 'update']);
        Route::patch('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

        // ---- Directions (CRUD complet) ----
        Route::apiResource('directions', DirectionController::class);

        // ---- Départements (CRUD complet) ----
        Route::apiResource('departments', DepartmentController::class);

        // ---- Utilisateurs (CRUD complet + actions spécifiques) ----
        Route::apiResource('users', UserController::class);
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::put('/users/{id}/affectation', [UserController::class, 'updateAffectation']);
        Route::post('/users/{id}/reactivate', [UserController::class, 'reactivate']);

        // ---- Journaux d'audit (lecture seule) ----
        Route::get('/journals', [JournalController::class, 'index']);
    });
});