<?php

namespace App\Support\Movies;

use Illuminate\Support\Collection;

/**
 * Champs multi-valeurs d'un film (genre, studio…), stockés comme une seule chaîne séparée par des
 * virgules (« Action, Science-Fiction »). Centralise l'éclatement en valeurs individuelles utilisé
 * par les filtres, les statistiques et les favoris — auparavant dupliqué à plusieurs endroits.
 */
class FieldList
{
    /**
     * Éclate une valeur séparée par des virgules en valeurs individuelles, débarrassées des espaces
     * superflus et des entrées vides. Retourne une collection vide pour null ou une chaîne vide.
     *
     * @return Collection<int, string>
     */
    public static function split(?string $value): Collection
    {
        return collect(explode(',', (string) $value))
            ->map(fn (string $part) => trim($part))
            ->filter(fn (string $part) => $part !== '')
            ->values();
    }
}
