<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\Poste;
use App\Models\Service;
use App\Models\Affectation;
use App\Models\Journal;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SetupController extends Controller
{
    // Vérifier si l'application est déjà configurée (appelé par React au démarrage)
    public function checkSetup()
    {
        $company = Company::first();
        $isConfigured = $company ? $company->is_configured : false;

        return response()->json([
            'is_configured' => $isConfigured,
            'company' => $company
        ]);
    }



public function check()
{
    try {
        // Vérifie par exemple si une entreprise a déjà été configurée
        $isConfigured = Company::exists(); 

        return response()->json([
            'configured' => $isConfigured
        ]);
    } catch (\Exception $e) {
        // En cas d'erreur (ex: table non migrée), retourne l'erreur proprement
        return response()->json([
            'configured' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
    // Enregistrer la configuration initiale
    public function storeSetup(Request $request)
    {
        // Vérifier si c'est déjà configuré pour bloquer les doubles configurations
        $existingCompany = Company::first();
        if ($existingCompany && $existingCompany->is_configured) {
            return response()->json(['message' => 'L\'application est déjà configurée.'], 403);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_size' => 'required|in:small,large',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|min:6',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // 1. Gérer le logo si présent
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            // 2. Créer ou mettre à jour l'entreprise
            $company = Company::updateOrCreate(
                ['id' => 1],
                [
                    'name' => $request->company_name,
                    'size' => $request->company_size,
                    'logo_path' => $logoPath,
                    'is_configured' => true,
                ]
            );

            // 3. Créer un service par défaut (ex: Direction Générale ou Informatique)
            $service = Service::create([
                'company_id' => $company->id,
                'name' => 'Administration Générale'
            ]);

            // 4. Récupérer le poste Administrateur
            $posteAdmin = Poste::where('level', 'admin')->first();

            // 5. Créer le compte Super Admin
            $admin = User::create([
                'company_id' => $company->id,
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'status' => true,
            ]);

            // 6. Affecter l'admin à son poste
            Affectation::create([
                'user_id' => $admin->id,
                'poste_id' => $posteAdmin->id,
                'service_id' => $service->id,
                'is_active' => true,
                'started_at' => now(),
            ]);

            // 7. Journaliser l'action
            Journal::create([
                'user_id' => $admin->id,
                'action' => 'APP_SETUP_COMPLETED',
                'description' => 'Configuration initiale de l\'application et création de l\'administrateur.',
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Configuration initiale réussie avec succès !',
                'company' => $company
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la configuration : ' . $e->getMessage()
            ], 500);
        }
    }
}