<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    /**
     * Liste paginée des journaux d'audit de la company.
     * Réservé aux grantors (admin/dg/directeur/responsable_departement/chef_service).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Journal::with('user:id,name,email')
            ->whereHas('user', function ($q) use ($request) {
                $q->where('company_id', $request->user()->company_id);
            })
            ->orWhereNull('user_id');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($action = $request->string('action')->trim()->toString()) {
            $query->where('action', $action);
        }

        $perPage = min(100, max(10, $request->integer('per_page', 25)));
        $journals = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($journals);
    }
}