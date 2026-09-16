<?php

namespace App\Actions\Watchlist;

use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use App\Support\Movies\ReleaseWindow;

class AddCollectionToWatchlist
{
    /**
     * Ajoute d'un coup tous les films pas encore présents d'une saga TMDB,
     * chacun étiqueté "cinéma" ou "streaming" selon sa propre date de sortie,
     * exactement comme un ajout simple.
     *
     * @return array{added: int, message: string}
     */
    public function handle(int $collectionId, TmdbClient $tmdb): array
    {
        $parts = $tmdb->collectionFilms($collectionId);

        if (empty($parts)) {
            return ['added' => 0, 'message' => 'Impossible de récupérer cette saga.'];
        }

        $added = 0;

        foreach ($parts as $part) {
            if (WatchlistItem::where('tmdb_id', $part['tmdb_id'])->exists()) {
                continue;
            }

            $movie = $tmdb->find($part['tmdb_id']);

            if (! $movie) {
                continue;
            }

            $window = ReleaseWindow::classify($movie['release_date'] ?? $part['release_date'] ?? null);

            WatchlistItem::create([
                ...$movie,
                'source' => in_array($window, ['upcoming', 'in_cinema'], true) ? 'cinema' : 'streaming',
                'status' => 'to_watch',
                'watched_at' => null,
            ]);
            $added++;
        }

        $message = $added > 0
            ? $added.' film'.($added > 1 ? 's' : '').' de la saga ajouté'.($added > 1 ? 's' : '').' à votre liste.'
            : 'Tous les films de cette saga sont déjà dans votre liste.';

        return ['added' => $added, 'message' => $message];
    }
}
