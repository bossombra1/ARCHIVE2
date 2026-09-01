<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DepartmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $departments = Department::with('direction:id,name')
            ->withCount('services')
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get();

        return response()->json($departments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'direction_id' => 'nullable|exists:directions,id',
        ]);

        $department = Department::create([
            'company_id' => $request->user()->company_id,
            'direction_id' => $validated['direction_id'],
            'name' => $validated['name'],
        ]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'DEPARTMENT_CREATE',
            'description' => "Création du département #{$department->id} : {$department->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json($department, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'direction_id' => 'nullable|exists:directions,id',
        ]);

        $department->update($validated);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'DEPARTMENT_UPDATE',
            'description' => "Mise à jour du département #{$department->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json($department);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $department = Department::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        if ($department->services()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : ce département contient des services.',
            ], 422);
        }

        $department->delete();

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'DEPARTMENT_DELETE',
            'description' => "Suppression du département #{$id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Département supprimé.']);
    }
}