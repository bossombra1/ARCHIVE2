<?php

namespace App\Http\Controllers;

use App\Models\Poste;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosteController extends Controller
{
    /**
     * Liste tous les postes (lecture seule - postes globaux partagés entre companies).
     */
    public function index(): JsonResponse
    {
        $postes = Poste::withCount('affectations')
            ->orderBy('id')
            ->get();

        return response()->json($postes);
    }

    /**
     * Détail d'un poste.
     */
    public function show(int $id): JsonResponse
    {
        $poste = Poste::withCount('affectations')->findOrFail($id);
        return response()->json($poste);
    }
}