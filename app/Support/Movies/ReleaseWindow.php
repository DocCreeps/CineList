<?php

namespace App\Support\Movies;

use Illuminate\Support\Carbon;

/**
 * Classe utilitaire sans état, sans dépendance à la base de données ou à un
 * composant Livewire : facile à tester isolément et réutilisable partout
 * (actions, composants, futures commandes...).
 */
class ReleaseWindow
{
    /**
     * Classifie un film par sa date de sortie : pas encore sorti, sorti assez
     * récemment pour être plausiblement encore en salles (~2 mois, même fenêtre
     * que la page "À venir"), ou plus ancien.
     */
    public static function classify(?string $releaseDate): string
    {
        if (blank($releaseDate)) {
            return 'old';
        }

        $date = Carbon::parse($releaseDate);

        if ($date->isFuture()) {
            return 'upcoming';
        }

        return $date->diffInDays(now()) <= 60 ? 'in_cinema' : 'old';
    }
}
