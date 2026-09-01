<?php

namespace App\Models;

use RuntimeException;

/**
 * Levée quand un utilisateur possède plusieurs affectations actives
 * simultanément. La stratégie retenue est le refus par défaut : on ne
 * sélectionne jamais arbitrairement l'une d'entre elles.
 */
class AmbiguousAffectationException extends RuntimeException
{
    public function __construct(public int $userId, public int $count)
    {
        parent::__construct(
            "L'utilisateur #{$userId} possède {$count} affectations actives simultanément. "
            . "Veuillez clôturer les affectations obsolètes avant de continuer."
        );
    }
}
