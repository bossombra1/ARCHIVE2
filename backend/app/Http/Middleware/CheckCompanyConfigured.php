<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;

class CheckCompanyConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        // On vérifie s'il existe une entreprise configurée
        $company = Company::first();

        // Si aucune entreprise n'existe ou si elle n'est pas configurée
        if (!$company || !$company->is_configured) {
            return response()->json([
                'error' => 'COMPANY_NOT_CONFIGURED',
                'message' => 'L\'application n\'est pas encore configurée par l\'administrateur.'
            ], 403);
        }

        return $next($request);
    }
}