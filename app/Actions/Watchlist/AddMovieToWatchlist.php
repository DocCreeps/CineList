<?php

namespace App\Actions\Watchlist;

use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use App\Models\Movie;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use Illuminate\Database\UniqueConstraintViolationException;

class AddMovieToWatchlist
{
    /**
     * Les métadonnées du film viennent exclusivement de TMDB (jamais du navigateur) : les propriétés
     * publiques d'un composant Livewire, comme la liste de résultats affichée, sont modifiables côté
     * client et ne doivent pas alimenter la table `movies`, partagée entre tous les membres. Si TMDB
     * ne répond pas, l'ajout échoue proprement et l'utilisateur peut réessayer.
     *
     * @return array{added: bool, message: string}
     */
    public function handle(TmdbClient $tmdb, string $tmdbId, string $source, string $status): array
    {
        $watchSource = WatchSource::tryFrom($source);
        $watchStatus = WatchStatus::tryFrom($status);
        abort_unless($watchSource !== null && $watchStatus !== null, 422);

        if (WatchlistItem::whereTmdbId($tmdbId)->exists()) {
            return ['added' => false, 'message' => 'Ce film est déjà dans votre liste.'];
        }

        $details = $tmdb->find($tmdbId);

        if (! $details) {
            return ['added' => false, 'message' => 'Impossible de récupérer ce film pour le moment, réessayez dans un instant.'];
        }

        // La fiche du film est partagée : créée au premier ajout, réutilisée (et rafraîchie) par les suivants.
        $movie = Movie::syncFromTmdb($details);

        // "Déjà vue"/"Revoir" sont ajoutés déjà vus, donc horodatés et comptés immédiatement.
        $alreadySeen = $watchStatus !== WatchStatus::ToWatch;

        try {
            WatchlistItem::create([
                'movie_id' => $movie->id,
                'source' => $watchSource->value,
                'status' => $watchStatus->value,
                'watched_at' => $alreadySeen ? now() : null,
                'watch_count' => $alreadySeen ? 1 : 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Double clic ou deux onglets : l'autre requête a ajouté le film entre le test et l'insertion.
            return ['added' => false, 'message' => 'Ce film est déjà dans votre liste.'];
        }

        return ['added' => true, 'message' => 'Film ajouté à votre liste.'];
    }
}
