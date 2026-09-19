<?php

namespace App\Livewire\Concerns;

use App\Actions\Watchlist\AddCollectionToWatchlist;
use App\Actions\Watchlist\AddMovieToWatchlist;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use App\Support\Movies\ReleaseWindow;

/**
 * Comportement partagé par toutes les pages qui listent des films TMDB (résultats
 * de recherche, sorties à venir) : ouverture de la modale de détails et ajout d'un
 * film à la watchlist. $results est la liste actuellement affichée sur la page ;
 * elle sert de source de repli quand un film n'a pas encore été récupéré
 * individuellement auprès de TMDB.
 */
trait InteractsWithMovies
{
    public array $results = [];

    public ?array $selectedMovie = null;
    public bool $showModal = false;

    /**
     * Ouvre la modale de résumé. Un film déjà dans la watchlist a tout en local (aucune requête
     * nécessaire) sauf la bande-annonce, les films similaires et la saga, qui ne sont pas
     * persistés et sont toujours récupérés en direct (chacun mis en cache par TmdbClient : les
     * réouvertures sont donc gratuites). Un film pas encore ajouté est entièrement récupéré
     * auprès de TMDB, avec la liste $results courante en repli.
     */
    public function showDetails(string $tmdbId, TmdbClient $tmdb): void
    {
        // Un identifiant TMDB est toujours numérique ; rejette toute autre valeur avant de
        // l'interpoler dans l'URL de l'API et dans les clés de cache (voir TmdbClient).
        abort_unless(ctype_digit($tmdbId), 422);

        $fetched = $tmdb->find($tmdbId);
        $similar = $fetched ? $tmdb->similarFilms($tmdbId) : [];
        $providers = $tmdb->watchProviders($tmdbId);
        $collection = ($fetched && !empty($fetched['collection_id']))
            ? ['id' => $fetched['collection_id'], 'name' => $fetched['collection_name']]
            : null;

        $item = WatchlistItem::where('tmdb_id', $tmdbId)->first();
        if ($item) {
            $this->selectedMovie = [
                'title' => $item->title,
                'year' => $item->year,
                'poster_url' => $item->poster_url,
                'director' => $item->director,
                'actors' => $item->actors,
                'plot' => $item->plot,
                'genre' => $item->genre,
                'runtime' => $item->runtime,
                'imdb_rating' => $item->imdb_rating,
                'trailer_key' => $fetched['trailer_key'] ?? null,
                'trailer_lang' => $fetched['trailer_lang'] ?? null,
                'similar' => $similar,
                'collection' => $collection,
                'watch_providers' => $providers,
                // Présent uniquement pour les films déjà dans la watchlist — utilisé par la modale
                // pour afficher le champ "note" en texte libre, qui n'a pas d'autre interface.
                'item_id' => $item->id,
                'note' => $item->note,
                'status' => $item->status,
                'personal_rating' => $item->personal_rating,
            ];
            $this->showModal = true;
            return;
        }

        $fallback = collect($this->results)->firstWhere('tmdb_id', $tmdbId);
        if (! $fetched && ! $fallback) {
            $this->dispatch('toast', message: 'Détails indisponibles pour ce film.', type: 'error');
            return;
        }

        $this->selectedMovie = [
            'title' => $fetched['title'] ?? $fallback['title'] ?? '',
            'year' => $fetched['year'] ?? $fallback['year'] ?? null,
            'poster_url' => $fetched['poster_url'] ?? $fallback['poster_url'] ?? null,
            'director' => $fetched['director'] ?? $fallback['director'] ?? null,
            'actors' => $fetched['actors'] ?? $fallback['actors'] ?? null,
            'plot' => $fetched['plot'] ?? $fallback['plot'] ?? null,
            'genre' => $fetched['genre'] ?? null,
            'runtime' => $fetched['runtime'] ?? null,
            'imdb_rating' => $fetched['imdb_rating'] ?? null,
            'trailer_key' => $fetched['trailer_key'] ?? null,
            'trailer_lang' => $fetched['trailer_lang'] ?? null,
            'similar' => $similar,
            'collection' => $collection,
            'watch_providers' => $providers,
            // Pas encore dans la watchlist : pas d'id, pas de note enregistrée. Le champ
            // note reste affiché dans la modale mais désactivé tant que le film n'est pas ajouté.
            'item_id' => null,
            'note' => null,
            'status' => null,
            'personal_rating' => null,
        ];
        $this->showModal = true;
    }

    /**
     * Note personnelle de 1 à 5 étoiles, annulée si on reclique la même étoile. Placée dans ce
     * trait partagé (et pas seulement dans Dashboard) pour que la notation fonctionne aussi
     * depuis la modale de détails, incluse sur les pages recherche, à venir et accueil.
     */
    public function setPersonalRating(int $id, int $rating): void
    {
        abort_unless(in_array($rating, [1, 2, 3, 4, 5], true), 422);
        $item = WatchlistItem::findOrFail($id);
        $newRating = $item->personal_rating === $rating ? null : $rating;
        $item->update(['personal_rating' => $newRating]);

        // Garde la modale ouverte synchronisée si elle affiche ce même film.
        if ($this->selectedMovie && ($this->selectedMovie['item_id'] ?? null) === $id) {
            $this->selectedMovie['personal_rating'] = $newRating;
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedMovie = null;
    }

    /**
     * Enregistre le champ "note" en texte libre de la modale de détails. Accessible uniquement
     * pour un film déjà dans la watchlist, `$selectedMovie['item_id']` n'étant renseigné que
     * dans ce cas.
     */
    public function saveNote(): void
    {
        if (! $this->selectedMovie || empty($this->selectedMovie['item_id'])) {
            return;
        }

        $note = trim((string) ($this->selectedMovie['note'] ?? ''));

        if (mb_strlen($note) > 2000) {
            $this->dispatch('toast', message: 'La note est limitée à 2000 caractères.', type: 'error');

            return;
        }

        $item = WatchlistItem::findOrFail($this->selectedMovie['item_id']);
        $item->update(['note' => $note !== '' ? $note : null]);
        $this->selectedMovie['note'] = $item->note;

        $this->dispatch('toast', message: 'Note enregistrée.');
    }

    /** Ouvre la modale d'un film "à voir" tiré au hasard, pour aider à choisir quoi regarder. */
    public function surpriseMe(TmdbClient $tmdb): void
    {
        $item = WatchlistItem::where('status', 'to_watch')->inRandomOrder()->first();
        if (! $item) {
            $this->dispatch('toast', message: 'Aucun film "à voir" dans votre liste pour le moment.', type: 'info');
            return;
        }
        $this->showDetails($item->tmdb_id, $tmdb);
    }

    /** Délègue à ReleaseWindow ; conservé ici car Blade l'appelle via $this->releaseWindow(...). */
    public function releaseWindow(?string $releaseDate): string
    {
        return ReleaseWindow::classify($releaseDate);
    }

    public function add(TmdbClient $tmdb, AddMovieToWatchlist $action, string $tmdbId, string $source, string $status = 'to_watch'): void
    {
        abort_unless(ctype_digit($tmdbId), 422);

        $result = $action->handle($tmdb, $tmdbId, $source, $status, $this->results);
        $this->dispatch('toast', message: $result['message'], type: $result['added'] ? 'success' : 'info');
    }

    /**
     * Ajoute d'un coup à la watchlist tous les films pas encore présents d'une collection TMDB
     * (une saga), chacun étiqueté "cinéma" ou "streaming" selon sa propre date de sortie,
     * exactement comme un ajout simple.
     */
    public function addCollection(int $collectionId, TmdbClient $tmdb, AddCollectionToWatchlist $action): void
    {
        $result = $action->handle($collectionId, $tmdb);
        $this->dispatch('toast', message: $result['message'], type: $result['added'] > 0 ? 'success' : 'info');
        $this->closeModal();
    }
}
