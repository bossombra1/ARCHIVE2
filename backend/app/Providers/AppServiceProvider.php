<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Policies\DocumentPermissionPolicy;
use App\Policies\DocumentPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind singleton du service de visibilité documentaire :
        // une seule instance par cycle HTTP, partageable par policy + controller.
        $this->app->singleton(\App\Services\DocumentVisibilityService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel 11+ recommande Schema::defaultStringLength(191) pour compat MySQL/utf8mb4.
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        // Mode "strict" Eloquent désactivé pour éviter de casser les accès
        // existants via les attributs non fillable. À réactiver plus tard.
        Model::shouldBeStrict(false);

        // Enregistrement des policies
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(DocumentPermission::class, DocumentPermissionPolicy::class);
    }
}
