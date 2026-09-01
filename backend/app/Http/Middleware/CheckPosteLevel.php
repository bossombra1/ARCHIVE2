<?php

namespace App\Http\Middleware;

use App\Models\AmbiguousAffectationException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPosteLevel
{
    /**
     * Utilisation sur une route : ->middleware('poste:admin,dg')
     *
     * Vérifie que l'utilisateur connecté possède une affectation active
     * (is_active=true, started_at <= aujourd'hui, ended_at IS NULL ou
     * >= aujourd'hui) dont le poste.level est dans la liste autorisée.
     *
     * En cas d'affectation ambiguë (>1 active), on journalise et on refuse.
     */
    public function handle(Request $request, Closure $next, ...$levels): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        try {
            $activeAffectation = $user->affectations()
                ->active() // scope : is_active + started_at + ended_at
                ->with('poste')
                ->orderByDesc('started_at')
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'UNAUTHORIZED_POSTE',
                'message' => "Impossible de vérifier l'affectation.",
            ], 403);
        }

        if ($activeAffectation->isEmpty()) {
            return response()->json([
                'error' => 'UNAUTHORIZED_POSTE',
                'message' => "Vous n'avez pas d'affectation active ou votre poste ne permet pas cette action.",
            ], 403);
        }

        if ($activeAffectation->count() > 1) {
            // Journaliser l'anomalie
            \App\Models\Journal::create([
                'user_id' => $user->id,
                'action' => 'AMBIGUOUS_AFFECTATION',
                'description' => "Refus middleware poste : {$activeAffectation->count()} affectations actives détectées.",
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'AMBIGUOUS_AFFECTATION',
                'message' => "Plusieurs affectations actives détectées. Veuillez contacter l'administrateur.",
            ], 409);
        }

        $affectation = $activeAffectation->first();

        if (! in_array($affectation->poste->level, $levels)) {
            return response()->json([
                'error' => 'UNAUTHORIZED_POSTE',
                'message' => "Vous n'avez pas les droits requis pour effectuer cette action."
            ], 403);
        }

        return $next($request);
    }
}
