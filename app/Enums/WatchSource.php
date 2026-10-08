<?php

namespace App\Enums;

/**
 * Où un film est (ou a été) regardé : colonnes `watchlist_items.source` et `first_watched_source`.
 * Comme WatchStatus, l'enum centralise les valeurs autorisées sans changer le stockage en chaîne.
 */
enum WatchSource: string
{
    case Cinema = 'cinema';
    case Streaming = 'streaming';

    /** @return array<int, string> Toutes les valeurs, pour `in_array()` ou `Rule::in()`. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
