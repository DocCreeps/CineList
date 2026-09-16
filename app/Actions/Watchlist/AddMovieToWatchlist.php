<?php

namespace App\Actions\Watchlist;

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

        if (WatchlistItem::where('tmdb_id', $tmdbId)->exists()) {
            return ['added' => false, 'message' => 'Ce film est déjà dans votre liste.'];
        }

        $movie = $tmdb->find($tmdbId) ?? collect($fallbackResults)->firstWhere('tmdb_id', $tmdbId);

        if (! $movie) {
            return ['added' => false, 'message' => 'Impossible de récupérer ce film.'];
        }

        WatchlistItem::create([
            ...$movie,
            'source' => $source,
            'status' => $status,
            // "Déjà vue"/"Revoir" sont ajoutés déjà vus, donc horodatés immédiatement.
            'watched_at' => $status !== 'to_watch' ? now() : null,
        ]);

        return ['added' => true, 'message' => 'Film ajouté à votre liste.'];
    }
}
