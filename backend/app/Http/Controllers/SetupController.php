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
    public function checkSetup()
    {
        $company = Company::first();
        $isConfigured = $company ? (bool)$company->is_configured : false;

        return response()->json([
            'is_configured' => $isConfigured,
            'company' => $company
        ]);
    }

    public function check()
    {
        try {
            $isConfigured = Company::where('is_configured', true)->exists(); 

            return response()->json([
                'configured' => $isConfigured
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'configured' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeSetup(Request $request)
    {
        $existingCompany = Company::first();
        if ($existingCompany && $existingCompany->is_configured) {
            return response()->json(['message' => 'L\'application est déjà configurée.'], 403);
        }

        // Standardisation de la taille reçue depuis le frontend
        if ($request->has('company_size')) {
            $sizeInput = strtolower($request->company_size);
            if (str_contains($sizeInput, 'petit') || $sizeInput === 'small') {
                $request->merge(['company_size' => 'small']);
            } else {
                $request->merge(['company_size' => 'large']);
            }
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_size' => 'required|in:small,large',
            'admin_name'   => 'required|string|max:255',
            'admin_email'  => 'required|email|unique:users,email',
            'admin_password' => 'required|min:6',
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            $company = Company::updateOrCreate(
                ['id' => 1],
                [
                    'name' => $request->company_name,
                    'size' => $request->company_size,
                    'logo_path' => $logoPath,
                    'is_configured' => true,
                ]
            );

            $service = Service::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Administration Générale']
            );

            // S'assurer que le poste d'administrateur existe
            $posteAdmin = Poste::firstOrCreate(
                ['level' => 'admin'],
                ['name' => 'Administrateur Système']
            );

            $admin = User::create([
                'company_id' => $company->id,
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'status' => true,
            ]);

            Affectation::create([
                'user_id' => $admin->id,
                'poste_id' => $posteAdmin->id,
                'service_id' => $service->id,
                'is_active' => true,
                'started_at' => now(),
            ]);

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