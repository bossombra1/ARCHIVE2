<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'company.configured' => \App\Http\Middleware\CheckCompanyConfigured::class,
            'poste' => \App\Http\Middleware\CheckPosteLevel::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Pour toute requête API ou JSON, on renvoie du 401 JSON au lieu
        // d'essayer de rediriger vers une route 'login' qui n'existe pas
        // (ce qui causait l'erreur 500 "Route [login] not defined").
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Non authentifié.',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }
        });
    })->create();