<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Journal;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Afficher la liste des services de l'entreprise connectée (avec le département associé).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $services = Service::with('department')
            ->where('company_id', $user->company_id)
            ->get();

        return response()->json($services);
    }

    /**
     * Enregistrer un nouveau service.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = $request->user();

        $service = Service::create([
            'company_id' => $user->company_id,
            'department_id' => $request->department_id,
            'name' => $request->name,
        ]);

        // Journaliser l'action
        Journal::create([
            'user_id' => $user->id,
            'action' => 'SERVICE_CREATE',
            'description' => 'Création du service : ' . $service->name,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'message' => 'Service créé avec succès.',
            'data' => $service->load('department')
        ], 201);
    }

    /**
     * Afficher un service spécifique.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $service = Service::with('department')
            ->where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($service);
    }

    /**
     * Mettre à jour un service.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = $request->user();

        $service = Service::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        $service->update([
            'department_id' => $request->department_id,
            'name' => $request->name,
        ]);

        // Journaliser l'action
        Journal::create([
            'user_id' => $user->id,
            'action' => 'SERVICE_UPDATE',
            'description' => 'Mise à jour du service ID : ' . $service->id,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'message' => 'Service mis à jour avec succès.',
            'data' => $service->load('department')
        ]);
    }

    /**
     * Supprimer un service.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $service = Service::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        // Vérifier s'il y a des affectations liées avant suppression
        if ($service->affectations()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer ce service car des affectations y sont rattachées.'
            ], 422);
        }

        $serviceName = $service->name;
        $service->delete();

        // Journaliser l'action
        Journal::create([
            'user_id' => $user->id,
            'action' => 'SERVICE_DELETE',
            'description' => 'Suppression du service : ' . $serviceName,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'message' => 'Service supprimé avec succès.'
        ]);
    }
}