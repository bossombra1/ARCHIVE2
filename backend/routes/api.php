<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DocumentTypeController;

// ==========================================
// 1. ROUTES PUBLIQUES (Setup & Auth)
// ==========================================

// Vérifier si l'application est configurée (appelé par le front au démarrage)
Route::get('/setup/check', [SetupController::class, 'check']); // Garder une seule version

// Enregistrer la configuration initiale (exécuté une seule fois)
Route::post('/setup/store', [SetupController::class, 'storeSetup']);

// Connexion de l'utilisateur
Route::post('/login', [AuthController::class, 'login']);


// ==========================================
// 2. ROUTES PROTÉGÉES (Nécessite Sanctum + Entreprise configurée)
// ==========================================
Route::middleware(['auth:sanctum', 'company.configured'])->group(function () {
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Récupérer les informations de l'utilisateur connecté (avec son affectation et son poste)
    Route::get('/me', function (Request $request) {
        $user = $request->user();
        
        $affectationActive = $user->affectations()
            ->where('is_active', true)
            ->with(['poste', 'service', 'department', 'direction'])
            ->first();

        return response()->json([
            'user' => $user,
            'affectation' => $affectationActive
        ]);
    });

    Route::get('/user/current-affectation', function (Request $request) {
        $user = $request->user();
        $affectation = $user->affectations()->where('is_active', 1)->with('poste')->first();
        
        return response()->json([
            'poste' => $affectation ? $affectation->poste : null
        ]);
    }); // <-- ACCOLADE FERMANTE AJOUTÉE ICI

    Route::apiResource('document-types', DocumentTypeController::class); 

    Route::apiResource('services', ServiceController::class);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
   
    // ==========================================
    // 3. ROUTES RÉSERVÉES À L'ADMINISTRATEUR
    // ==========================================
    Route::middleware('poste:admin')->prefix('admin')->group(function () {
        Route::get('/journals', function () {
            return response()->json([
                'message' => 'Accès autorisé aux journaux du système (Réservé Admin)'
            ]);
        });
    });

});