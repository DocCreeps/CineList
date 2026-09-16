<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;

class UpdateWatchlistItemStatus
{
    /**
     * Vide la date de visionnage quand on repasse en "à voir" ; la pose la première fois
     * qu'un film passe en "vu"/"à revoir" ; mais conserve la date d'origine quand on bascule
     * entre "vu" et "à revoir" pour un même film.
     */
    public function handle(WatchlistItem $item, string $status): WatchlistItem
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);

        $watchedAt = match (true) {
            $status === 'to_watch' => null,
            $item->watched_at !== null => $item->watched_at,
            default => now(),
        };

        $item->update(['status' => $status, 'watched_at' => $watchedAt]);

        return $item;
    }
}
