<?php

namespace App\Actions\Watchlist;

use App\Enums\WatchSource;
use App\Enums\WatchStatus;
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
     *
     * Un film déjà vu (watched_at déjà renseigné) ne peut plus repasser en "à voir" — voir
     * WatchlistItem::alreadyWatched() : la demande est silencieusement ignorée (le film garde son
     * statut actuel) plutôt que de faire échouer tout un lot lors d'un changement groupé.
     */
    public function handle(WatchlistItem $item, string $status, ?string $firstWatchedSource = null): WatchlistItem
    {
        $newStatus = WatchStatus::tryFrom($status);
        abort_unless($newStatus !== null, 422);
        if ($firstWatchedSource !== null) {
            abort_unless(WatchSource::tryFrom($firstWatchedSource) !== null, 422);
        }

        if ($newStatus === WatchStatus::ToWatch && $item->alreadyWatched()) {
            return $item;
        }

        $watchedAt = match (true) {
            $newStatus === WatchStatus::ToWatch => null,
            $item->watched_at !== null => $item->watched_at,
            default => now(),
        };

        $isFirstWatch = $item->watched_at === null && $newStatus !== WatchStatus::ToWatch;
        $isRewatch = $newStatus === WatchStatus::Watched && $item->status === WatchStatus::ToRewatch;

        $item->update([
            'status' => $newStatus->value,
            'watched_at' => $watchedAt,
            'first_watched_source' => $item->first_watched_source ?? $firstWatchedSource,
            'watch_count' => $isFirstWatch || $isRewatch ? $item->watch_count + 1 : $item->watch_count,
        ]);

        return $item;
    }
}
