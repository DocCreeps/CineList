<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Limite de tentatives avec compte à rebours automatique côté navigateur.
 *
 * Au lieu d'écrire "Réessayez dans 42 secondes" dans une erreur de validation (qui reste figée
 * tant que l'utilisateur ne soumet pas à nouveau), le composant prévient le navigateur via
 * l'événement "throttled". Le composant Alpine "throttleCountdown" (resources/js/app.js) le capte,
 * décompte seconde par seconde, désactive le bouton d'envoi puis le réactive à zéro.
 *
 * La limite réelle reste appliquée ici, côté serveur : le compte à rebours n'est qu'un
 * affichage, le contourner dans le navigateur ne permet pas de réessayer plus tôt.
 */
trait ThrottlesWithCountdown
{
    /**
     * Retourne true (et déclenche le compte à rebours) si la clé a dépassé son quota.
     * Le compteur n'est pas incrémenté ici : c'est au composant d'appeler
     * RateLimiter::hit() sur un échec, comme avant.
     */
    protected function isThrottled(string $key, int $maxAttempts): bool
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $this->dispatch('throttled', seconds: RateLimiter::availableIn($key));

        return true;
    }
}
