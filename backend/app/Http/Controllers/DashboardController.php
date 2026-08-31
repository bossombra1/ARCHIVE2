<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        // Requêtes dynamiques basées sur la base de données réelle
        $totalDocuments = Document::where('company_id', $companyId)->count();
        $totalDocumentTypes = DocumentType::where('company_id', $companyId)->count();
        $totalServices = Service::where('company_id', $companyId)->count();
        $totalUsers = User::where('company_id', $companyId)->count();

        // Répartition des documents par type (pour les graphiques)
        $documentsByType = DocumentType::where('company_id', $companyId)
            ->withCount('documents')
            ->get()
            ->map(function ($type) {
                return [
                    'name' => $type->name,
                    'count' => $type->documents_count
                ];
            });

        return response()->json([
            'total_documents' => $totalDocuments,
            'total_document_types' => $totalDocumentTypes,
            'total_services' => $totalServices,
            'total_users' => $totalUsers,
            'documents_by_type' => $documentsByType,
        ]);
    }
}