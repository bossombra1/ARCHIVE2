<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimise les indexes de document_permissions et affectations.
 *
 * - document_permissions : index composite (document_id, target_type, target_id)
 *   pour les requêtes de lookup par permission, + index sur expires_at pour
 *   filtrer rapidement les permissions valides.
 *   On évite l'UNIQUE strict car la migration doit être non destructive :
 *   si des doublons existent déjà dans la base réelle, l'UNIQUE échouerait.
 *   L'unicité sera garantie côté applicatif (Form Request + service).
 *
 * - affectations : index (user_id, is_active, started_at) pour accélérer
 *   la recherche d'affectation active sans index unique (qui interdirait
 *   l'historique inactif).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_permissions', function (Blueprint $table) {
            $table->index(
                ['document_id', 'target_type', 'target_id'],
                'doc_perm_target_index'
            );
            $table->index('expires_at', 'doc_perm_expires_at_index');
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->index(
                ['user_id', 'is_active', 'started_at'],
                'aff_user_active_started_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_permissions', function (Blueprint $table) {
            $table->dropIndex('doc_perm_target_index');
            $table->dropIndex('doc_perm_expires_at_index');
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->dropIndex('aff_user_active_started_index');
        });
    }
};
