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

    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);

    // /me : renvoie user + affectation (avec poste.level) embarqués dans l'objet user
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

    // ==========================================
    // 3. LISTES MINIMALES (pour les sélecteurs du frontend)
    // ==========================================
    Route::get('/users/minimal', function (Request $request) {
        return response()->json(
            User::where('company_id', $request->user()->company_id)
                ->select('id', 'name', 'email')->orderBy('name')->get()
        );
    });

    Route::get('/postes', function () {
        return response()->json(
            Poste::select('id', 'name', 'level')->orderBy('id')->get()
        );
    });

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
    // 4. RESSOURCES PRINCIPALES
    // ==========================================
    Route::apiResource('document-types', DocumentTypeController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('directions', DirectionController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('users', UserController::class);
    Route::get('/postes', [PosteController::class, 'index']);
    Route::get('/postes/{poste}', [PosteController::class, 'show']);
    Route::get('/journals', [JournalController::class, 'index']);

    // Actions spécifiques sur les users
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::put('/users/{id}/affectation', [UserController::class, 'updateAffectation']);
    Route::post('/users/{id}/reactivate', [UserController::class, 'reactivate']);

    // ==========================================
    // 5. DASHBOARD
    // ==========================================
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ==========================================
    // 6. DOCUMENTS (cœur GED)
    // ==========================================
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::apiResource('documents', DocumentController::class);

    // ==========================================
    // 6.bis. PERMISSIONS DOCUMENTAIRES
    // ==========================================
    Route::get('/documents/{document}/permissions', [DocumentPermissionController::class, 'index'])
        ->name('documents.permissions.index');
    Route::post('/documents/{document}/permissions', [DocumentPermissionController::class, 'store'])
        ->name('documents.permissions.store');
    Route::delete('/document-permissions/{permission}', [DocumentPermissionController::class, 'destroy'])
        ->name('document-permissions.destroy');

    // ==========================================
    // 7. RÉSERVÉ À L'ADMINISTRATEUR
    // ==========================================
    Route::middleware('poste:admin')->prefix('admin')->group(function () {
        Route::get('/journals', function () {
            return response()->json([
                'message' => 'Accès autorisé aux journaux du système (Réservé Admin)'
            ]);
        });
    });
});