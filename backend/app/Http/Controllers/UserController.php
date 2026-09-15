<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Company;
use App\Models\Department;
use App\Models\Direction;
use App\Models\Journal;
use App\Models\Poste;
use App\Models\Service;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly PlanLimitService $planLimit)
    {
    }

    /**
     * Liste paginée des utilisateurs de la company.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['affectations' => function ($q) {
            $q->where('is_active', true)->with('poste:id,name,level', 'service:id,name', 'department:id,name', 'direction:id,name');
        }])
            ->where('company_id', $request->user()->company_id)
            ->select('id', 'name', 'email', 'status', 'lang', 'theme_color', 'created_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min(50, max(5, $request->integer('per_page', 15)));
        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Crée un nouvel utilisateur + son affectation active.
     */
   public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        if (! $this->planLimit->canAddUser($request->user()->company)) {
            return response()->json([
                'error' => 'PLAN_USER_LIMIT_REACHED',
                'message' => "Limite d'utilisateurs de votre forfait atteinte. Passez à un forfait supérieur pour ajouter de nouveaux utilisateurs.",
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->where('company_id', $companyId)],
            'password' => 'required|string|min:6',
            'poste_id' => 'required|exists:postes,id',
            'service_id' => 'required|exists:services,id',
            'department_id' => 'nullable|exists:departments,id',
            'direction_id' => 'nullable|exists:directions,id',
            'status' => 'boolean',
        ]);

        $user = User::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? true,
            'lang' => 'fr',
            'theme_color' => '#2563eb',
        ]);

        Affectation::create([
            'user_id' => $user->id,
            'poste_id' => $validated['poste_id'],
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'] ?? null,
            'direction_id' => $validated['direction_id'] ?? null,
            'is_active' => true,
            'started_at' => now(),
        ]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'USER_CREATE',
            'description' => "Création de l'utilisateur #{$user->id} : {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json($user, 201);
    }

    /**
     * Détail d'un utilisateur.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::with(['affectations.poste', 'affectations.service', 'affectations.department', 'affectations.direction'])
            ->where('company_id', $request->user()->company_id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($user);
    }

    /**
     * Met à jour un utilisateur (sans toucher au mot de passe).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)->where('company_id', $user->company_id)],
            'status' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'USER_UPDATE',
            'description' => "Mise à jour de l'utilisateur #{$user->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json($user);
    }

    /**
     * Réinitialise le mot de passe d'un utilisateur.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate(['password' => 'required|string|min:6']);
        $user = User::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        $user->update(['password' => Hash::make($request->password)]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'USER_PASSWORD_RESET',
            'description' => "Réinitialisation du mot de passe de l'utilisateur #{$user->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }

    /**
     * Met à jour l'affectation active d'un utilisateur (désactive l'ancienne + crée la nouvelle).
     */
    public function updateAffectation(Request $request, int $id): JsonResponse
    {
        $user = User::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'poste_id' => 'required|exists:postes,id',
            'service_id' => 'required|exists:services,id',
            'department_id' => 'nullable|exists:departments,id',
            'direction_id' => 'nullable|exists:directions,id',
        ]);

        // Désactive les anciennes affectations actives
        Affectation::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'ended_at' => now()]);

        // Crée la nouvelle
        Affectation::create([
            'user_id' => $user->id,
            'poste_id' => $validated['poste_id'],
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'] ?? null,
            'direction_id' => $validated['direction_id'] ?? null,
            'is_active' => true,
            'started_at' => now(),
        ]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'USER_AFFECTATION_UPDATE',
            'description' => "Mise à jour de l'affectation de l'utilisateur #{$user->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Affectation mise à jour.']);
    }

    /**
     * Supprime un utilisateur (soft delete via status = false + désactivation affectation).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        // Désactiver le compte
        $user->update(['status' => false]);
        Affectation::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'ended_at' => now()]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'USER_DEACTIVATE',
            'description' => "Désactivation de l'utilisateur #{$user->id} : {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Utilisateur désactivé.']);
    }

    /**
     * Réactive un utilisateur désactivé.
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        $user = User::where('company_id', $request->user()->company_id)
            ->where('id', $id)->firstOrFail();

        $user->update(['status' => true]);

        Journal::create([
            'user_id' => $request->user()->id,
            'action' => 'USER_REACTIVATE',
            'description' => "Réactivation de l'utilisateur #{$user->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Utilisateur réactivé.']);
    }
}