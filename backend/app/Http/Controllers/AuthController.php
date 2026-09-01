<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Journal;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 1. Rechercher l'utilisateur par son email
        $user = User::where('email', $request->email)->first();

        // 2. Vérifier si l'utilisateur existe et si le mot de passe correspond
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Journaliser la tentative échouée
            Journal::create([
                'user_id' => null,
                'action' => 'LOGIN_FAILED',
                'description' => 'Tentative de connexion échouée pour l\'email : ' . $request->email,
                'ip_address' => $request->ip()
            ]);

            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        // 3. VÉRIFICATION DU STATUT DU COMPTE
        if (!$user->status) {
            Journal::create([
                'user_id' => $user->id,
                'action' => 'LOGIN_BLOCKED_INACTIVE',
                'description' => 'Tentative de connexion bloquée : compte inactif pour ' . $user->email,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'error' => 'ACCOUNT_INACTIVE',
                'message' => 'Votre compte est inactif. Veuillez voir l\'administrateur.'
            ], 403);
        }

        // 3.bis. Vérifier que l'utilisateur possède au moins une affectation active
        // (is_active=true + started_at + ended_at). On ne refuse pas le login
        // s'il n'en a pas (il pourra consulter son profil), mais on journalise
        // pour permettre à l'admin d'intervenir.
        try {
            $hasActiveAffectation = $user->affectations()->active()->exists();
        } catch (\Throwable $e) {
            $hasActiveAffectation = false;
        }

        if (! $hasActiveAffectation) {
            Journal::create([
                'user_id' => $user->id,
                'action' => 'LOGIN_NO_ACTIVE_AFFECTATION',
                'description' => "Connexion sans affectation active pour {$user->email}. Accès documentaire refusé.",
                'ip_address' => $request->ip(),
            ]);
            // On laisse passer : l'utilisateur pourra accéder à son profil et
            // demander une régularisation. Les routes documentaires refuseront
            // d'elles-mêmes (DocumentPolicy).
        }

        // 4. Nettoyer les anciens tokens pour éviter l'accumulation
        $user->tokens()->delete();

        // 5. Création du token d'accès (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Journaliser la connexion réussie
        Journal::create([
            'user_id' => $user->id,
            'action' => 'LOGIN_SUCCESS',
            'description' => 'Connexion réussie pour l\'utilisateur ' . $user->name,
            'ip_address' => $request->ip()
        ]);

        // Même structure que /me, pour que le frontend dispose immédiatement
        // de l'affectation active sans avoir besoin d'un refresh.
        $affectationActive = $user->affectations()
            ->where('is_active', true)
            ->with(['poste:id,name,level', 'service:id,company_id,department_id,name', 'department:id,company_id,direction_id,name', 'direction:id,company_id,name'])
            ->first();

        $userData = $user->toArray();
        $userData['affectation'] = $affectationActive;
        $userData['company'] = $user->company;

        return response()->json([
            'message' => 'Connexion réussie',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $userData
        ]);
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user) {
            // Journaliser la déconnexion
            Journal::create([
                'user_id' => $user->id,
                'action' => 'LOGOUT',
                'description' => 'Déconnexion de l\'utilisateur ' . $user->name,
                'ip_address' => $request->ip()
            ]);

            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Déconnexion effectuée avec succès.']);
    }
}