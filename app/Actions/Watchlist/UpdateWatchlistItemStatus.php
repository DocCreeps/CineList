<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;

class UpdateWatchlistItemStatus
{
    /**
     * Vide la date de visionnage quand on repasse en "à voir" ; la pose la première fois
     * qu'un film passe en "vu"/"à revoir" ; mais conserve la date d'origine quand on bascule
     * entre "vu" et "à revoir" pour un même film.
     *
     * `$firstWatchedSource` (cinéma/streaming) enregistre où le film a été vu la toute première
     * fois ; posé uniquement la première fois (jamais écrasé par un aller-retour ultérieur entre
     * "vu" et "à revoir"), et distinct du champ `source` du film.
     *
     * `watch_count` (nombre de fois vu) s'incrémente automatiquement à chaque véritable
     * "visionnage" : la toute première fois qu'un film passe en "vu" ou "à revoir" (peu importe
     * lequel des deux, au cas où "à revoir" est posé directement sans passer par "vu"), puis à
     * chaque fois qu'on repasse de "à revoir" à "vu" (un revisionnage). Un simple aller-retour
     * entre "vu" et "à revoir" sans repasser par "vu" ne compte pas deux fois le même visionnage.
     */
    public function handle(WatchlistItem $item, string $status, ?string $firstWatchedSource = null): WatchlistItem
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);
        if ($firstWatchedSource !== null) {
            abort_unless(in_array($firstWatchedSource, ['cinema', 'streaming'], true), 422);
        }

        $watchedAt = match (true) {
            $status === 'to_watch' => null,
            $item->watched_at !== null => $item->watched_at,
            default => now(),
        };

        $isFirstWatch = $item->watched_at === null && $status !== 'to_watch';
        $isRewatch = $status === 'watched' && $item->status === 'to_rewatch';

        $item->update([
            'status' => $status,
            'watched_at' => $watchedAt,
            'first_watched_source' => $item->first_watched_source ?? $firstWatchedSource,
            'watch_count' => $isFirstWatch || $isRewatch ? $item->watch_count + 1 : $item->watch_count,
        ]);

        return $item;
    }
}
