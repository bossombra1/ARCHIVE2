<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes organisationnelles service_id / department_id / direction_id
 * à la table `documents`.
 *
 * Stratégie (lot 1) :
 *   - Ajout en colonnes NULLABLE (non destructive).
 *   - Aucune contrainte NOT NULL dans cette migration : on préserve les
 *     documents existants éventuels (la base réelle n'a pas été inspectée).
 *   - Le durcissement éventuel vers NOT NULL se fera dans une migration
 *     séparée après régularisation des données (lot 7).
 *   - Les FK sont ajoutées avec ON DELETE RESTRICT pour éviter qu'une
 *     suppression en cascade d'un service/département/direction n'efface
 *     un document ; l'admin devra réaffecter les documents concernés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable()->after('uploaded_by');
            $table->unsignedBigInteger('department_id')->nullable()->after('service_id');
            $table->unsignedBigInteger('direction_id')->nullable()->after('department_id');

            $table->foreign('service_id')
                ->references('id')->on('services')
                ->onDelete('restrict');

            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onDelete('restrict');

            $table->foreign('direction_id')
                ->references('id')->on('directions')
                ->onDelete('restrict');

            $table->index('service_id');
            $table->index('department_id');
            $table->index('direction_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Supprimer d'abord les FK et indexes pour éviter les verrous
            if (Schema::hasColumn('documents', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropIndex(['service_id']);
            }
            if (Schema::hasColumn('documents', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropIndex(['department_id']);
            }
            if (Schema::hasColumn('documents', 'direction_id')) {
                $table->dropForeign(['direction_id']);
                $table->dropIndex(['direction_id']);
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'department_id', 'direction_id']);
        });
    }
};
