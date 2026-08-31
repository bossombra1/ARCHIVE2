<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Journal;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    /**
     * Afficher la liste des types de documents de l'entreprise connectée.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Récupérer uniquement les types liés à l'entreprise de l'utilisateur
        $documentTypes = DocumentType::where('company_id', $user->company_id)->get();

        return response()->json($documentTypes);
    }

    /**
     * Enregistrer un nouveau type de document.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
        ]);

        $user = $request->user();

        $documentType = DocumentType::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
        ]);

        // Journaliser l'action
        Journal::create([
            'user_id' => $user->id,
            'action' => 'DOCUMENT_TYPE_CREATE',
            'description' => 'Création du type de document : ' . $documentType->name,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'message' => 'Type de document créé avec succès.',
            'data' => $documentType
        ], 201);
    }

    /**
     * Afficher un type de document spécifique.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $documentType = DocumentType::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($documentType);
    }

    /**
     * Mettre à jour un type de document.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:191',
        ]);

        $user = $request->user();

        $documentType = DocumentType::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        $documentType->update([
            'name' => $request->name,
        ]);

        // Journaliser l'action
        Journal::create([
            'user_id' => $user->id,
            'action' => 'DOCUMENT_TYPE_UPDATE',
            'description' => 'Mise à jour du type de document ID : ' . $documentType->id,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'message' => 'Type de document mis à jour avec succès.',
            'data' => $documentType
        ]);
    }

    /**
     * Supprimer un type de document.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $documentType = DocumentType::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        // Optionnel : Empêcher la suppression s'il y a des documents liés
        if ($documentType->documents()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer ce type car des documents y sont rattachés.'
            ], 422);
        }

        $typeName = $documentType->name;
        $documentType->delete();

        // Journaliser l'action
        Journal::create([
            'user_id' => $user->id,
            'action' => 'DOCUMENT_TYPE_DELETE',
            'description' => 'Suppression du type de document : ' . $typeName,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'message' => 'Type de document supprimé avec succès.'
        ]);
    }
}