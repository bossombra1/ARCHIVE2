<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckAppExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $productionDate = env('APP_PRODUCTION_DATE', '2026-01-01');
        $expirationYears = (int) env('APP_EXPIRATION_YEARS', 2);

        $expiryDate = Carbon::parse($productionDate)->addYears($expirationYears);

        // Si la date actuelle dépasse la date d'expiration
        if (Carbon::now()->greaterThan($expiryDate)) {
            return response()->json([
                'error' => 'APPLICATION_EXPIRED',
                'message' => 'La licence de cette application a expiré. Veuillez contacter l\'administrateur ou le fournisseur.'
            ], 403);
        }

        return $next($request);
    }
}
