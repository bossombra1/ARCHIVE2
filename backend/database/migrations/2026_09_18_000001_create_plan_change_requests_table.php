<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de changement de forfait.
 *
 * L'Administrateur Système de l'entreprise ne peut PLUS changer le forfait
 * librement (PUT /company/size supprimé) : il soumet une DEMANDE, qui reste
 * "pending" jusqu'à validation MANUELLE hors application (directement en
 * base, ou via la commande artisan `php artisan plan:process`).
 *
 * Cycle de vie :
 *   pending   -> demande soumise par l'admin, en attente de l'opérateur ;
 *   approved  -> validé manuellement hors app : l'admin peut l'appliquer
 *                depuis l'interface ("Appliquer") ;
 *   rejected  -> refusé manuellement hors app : l'admin peut re-soumettre ;
 *   applied   -> forfait effectivement basculé (historique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');

            $table->string('current_size', 50);   // palier au moment de la demande
            $table->string('requested_size', 50); // palier demandé

            $table->enum('status', ['pending', 'approved', 'rejected', 'applied'])
                ->default('pending');

            // Renseignés lors de la décision manuelle (hors app).
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->string('note', 500)->nullable(); // raison du refus, référence, etc.

            $table->timestamps();

            $table->index(['company_id', 'status'], 'plan_change_requests_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_change_requests');
    }
};
