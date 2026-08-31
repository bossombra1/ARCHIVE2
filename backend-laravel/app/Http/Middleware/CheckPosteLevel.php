<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPosteLevel
{
    /**
     * Utilisation sur une route : ->middleware('poste:admin,dg')
     */
    public function handle(Request $request, Closure $next, ...$levels): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // Récupérer l'affectation active de l'utilisateur avec son poste
        $activeAffectation = $user->affectations()
            ->where('is_active', true)
            ->with('poste')
            ->first();

        if (!$activeAffectation || !in_array($activeAffectation->poste->level, $levels)) {
            return response()->json([
                'error' => 'UNAUTHORIZED_POSTE',
                'message' => 'Vous n\'avez pas les droits requis pour effectuer cette action.'
            ], 403);
        }

        return $next($request);
    }
}