<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permissions d'action accordées par l'Administrateur Système à un
 * utilisateur : modifier, supprimer et/ou ajouter des documents.
 *
 * Différences avec `document_permissions` (visibilité) :
 *   - grantor unique : seul poste.level = 'admin' peut créer/révoquer ces
 *     lignes (vérifié en Policy, pas en base) ;
 *   - cible toujours un utilisateur direct (pas de poste/service) ;
 *   - vient en PLUS des droits hiérarchiques de DocumentVisibilityService,
 *     ne les remplace jamais.
 *
 * Une seule ligne par (company_id, user_id) : accorder un nouveau droit à
 * un utilisateur qui en a déjà un met à jour la ligne existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_action_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('granted_by')->constrained('users')->onDelete('cascade');

            $table->boolean('can_modify')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_add')->default(false);

            // Portée de can_modify / can_delete :
            //   'all'      -> tous les documents de la company
            //   'specific' -> uniquement les documents listés dans
            //                 document_action_grant_targets
            // can_add n'a pas de portée : droit global de création.
            $table->enum('scope', ['all', 'specific'])->default('specific');

            $table->dateTime('expires_at')->nullable(); // accès temporaire, comme document_permissions
            $table->timestamps();

            $table->unique(['company_id', 'user_id'], 'doc_action_grant_company_user_unique');
            $table->index('expires_at', 'doc_action_grant_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_action_grants');
    }
};