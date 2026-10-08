<?php

namespace App\Enums;

/**
 * Statut d'un film dans la liste d'un membre (colonne `watchlist_items.status`).
 *
 * Les colonnes restent de simples chaînes en base et sur le modèle (les vues et les collections
 * comparent encore `$item->status` à ces valeurs) : l'enum est la source unique des valeurs
 * autorisées, pour valider les entrées (`tryFrom`) et éviter de retaper les littéraux.
 */
enum WatchStatus: string
{
    case ToWatch = 'to_watch';
    case Watched = 'watched';
    case ToRewatch = 'to_rewatch';

    /** @return array<int, string> Toutes les valeurs, pour `in_array()` ou `Rule::in()`. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuts d'un film déjà vu au moins une fois : « vu » et « à revoir », sous forme de valeurs
     * (pour une requête SQL : `whereIn('status', WatchStatus::seenValues())`).
     *
     * @return array<int, string>
     */
    public static function seenValues(): array
    {
        return array_map(fn (self $status) => $status->value, self::seen());
    }

    /**
     * Les mêmes statuts sous forme de cas d'enum (pour filtrer une collection en mémoire, dont les
     * éléments portent maintenant des cas d'enum : `$items->whereIn('status', WatchStatus::seen())`).
     *
     * @return array<int, self>
     */
    public static function seen(): array
    {
        return [self::Watched, self::ToRewatch];
    }
}
