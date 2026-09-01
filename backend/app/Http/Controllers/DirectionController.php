<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DirectionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $directions = Direction::withCount('departments')
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get();

        return response()->json($directions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
        ]);

        $direction = Direction::create([
            'company_id' => $request->user()->company_id,
            'name' => $validated['name'],
        ]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'DIRECTION_CREATE',
            'description' => "Création de la direction #{$direction->id} : {$direction->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json($direction, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $direction = Direction::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:191',
        ]);

        $direction->update($validated);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'DIRECTION_UPDATE',
            'description' => "Mise à jour de la direction #{$direction->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json($direction);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $direction = Direction::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        if ($direction->departments()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : cette direction contient des départements.',
            ], 422);
        }

        $direction->delete();

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'DIRECTION_DELETE',
            'description' => "Suppression de la direction #{$id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Direction supprimée.']);
    }
}