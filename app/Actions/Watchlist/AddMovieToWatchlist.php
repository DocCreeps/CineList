<?php

namespace App\Actions\Watchlist;

use App\Models\Movie;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;

class AddMovieToWatchlist
{
    /**
     * @param  array<int, array<string, mixed>>  $fallbackResults  Utilisés si TMDB est injoignable.
     * @return array{added: bool, message: string}
     */
    public function handle(TmdbClient $tmdb, string $tmdbId, string $source, string $status, array $fallbackResults = []): array
    {
        abort_unless(in_array($source, ['cinema', 'streaming'], true), 422);
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);

        if (WatchlistItem::whereTmdbId($tmdbId)->exists()) {
            return ['added' => false, 'message' => 'Ce film est déjà dans votre liste.'];
        }

        $details = $tmdb->find($tmdbId) ?? collect($fallbackResults)->firstWhere('tmdb_id', $tmdbId);

        if (! $details) {
            return ['added' => false, 'message' => 'Impossible de récupérer ce film.'];
        }

        // La fiche du film est partagée : créée au premier ajout, réutilisée (et rafraîchie) par les suivants.
        $movie = Movie::syncFromTmdb($details);

        WatchlistItem::create([
            'movie_id' => $movie->id,
            'source' => $source,
            'status' => $status,
            // "Déjà vue"/"Revoir" sont ajoutés déjà vus, donc horodatés et comptés immédiatement.
            'watched_at' => $status !== 'to_watch' ? now() : null,
            'watch_count' => $status !== 'to_watch' ? 1 : 0,
        ]);

        return ['added' => true, 'message' => 'Film ajouté à votre liste.'];
    }
}
