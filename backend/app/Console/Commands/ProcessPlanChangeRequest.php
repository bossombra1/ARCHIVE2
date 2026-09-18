<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\PlanChangeRequest;
use Illuminate\Console\Command;

/**
 * Outil d'exploitation (hors application) pour traiter les demandes de
 * changement de forfait soumises par l'Administrateur Système.
 *
 * Validation manuelle — équivalent à un UPDATE direct en base, mais en
 * maintenant decided_at / note et en écrivant une trace dans le journal :
 *
 *   php artisan plan:process 3 --approve
 *   php artisan plan:process 3 --reject --note="Justificatif manquant"
 *
 * L'application elle-même n'a ensuite plus qu'à appliquer la demande
 * approuvée (bouton "Appliquer" de l'admin, endpoint dédié). Une demande
 * 'pending' ne modifie JAMAIS le forfait.
 */
class ProcessPlanChangeRequest extends Command
{
    protected $signature = 'plan:process
                            {request : ID de la demande (plan_change_requests)}
                            {--approve : Approuver la demande}
                            {--reject : Refuser la demande}
                            {--note= : Note optionnelle (raison du refus, référence…)}';

    protected $description = 'Valider (approuver/refuser) manuellement une demande de changement de forfait, hors application';

    public function handle(): int
    {
        if ($this->option('approve') === $this->option('reject')) {
            $this->error('Choisissez exactement une option : --approve OU --reject.');

            return self::FAILURE;
        }

        $planRequest = PlanChangeRequest::find($this->argument('request'));

        if (! $planRequest) {
            $this->error("Demande introuvable (id={$this->argument('request')}).");

            return self::FAILURE;
        }

        if (! $planRequest->isPending()) {
            $this->error("La demande #{$planRequest->id} n'est pas en attente (statut actuel : {$planRequest->status}).");

            return self::FAILURE;
        }

        $status = $this->option('approve')
            ? PlanChangeRequest::STATUS_APPROVED
            : PlanChangeRequest::STATUS_REJECTED;

        $planRequest->update([
            'status' => $status,
            'decided_at' => now(),
            'note' => $this->option('note'),
        ]);

        Journal::create([
            'action' => 'PLAN_CHANGE_' . mb_strtoupper($status),
            'description' => "Traitement manuel (artisan plan:process) de la demande #{$planRequest->id} : "
                . "{$planRequest->current_size} -> {$planRequest->requested_size} ({$status}).",
        ]);

        $this->info("Demande #{$planRequest->id} {$status}.");

        if ($status === PlanChangeRequest::STATUS_APPROVED) {
            $this->line("L'Administrateur Système peut maintenant l'appliquer depuis l'interface (bouton « Appliquer »).");
        }

        return self::SUCCESS;
    }
}
