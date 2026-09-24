<?php

namespace App\Livewire\Concerns;

use App\Actions\Watchlist\AddCollectionToWatchlist;
use App\Actions\Watchlist\AddMovieToWatchlist;
use App\Actions\Watchlist\UpdateWatchlistItemStatus;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use App\Support\Movies\ReleaseWindow;
use Livewire\Attributes\Computed;

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

    /** Panneau « Casting complet » ouvert par-dessus la modale de détails. */
    public bool $showCast = false;

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

        $this->showCast = false;

        $fetched = $tmdb->find($tmdbId);
        $similar = $fetched ? $tmdb->similarFilms($tmdbId) : [];
        $providers = $tmdb->watchProviders($tmdbId);
        $collection = ($fetched && !empty($fetched['collection_id']))
            ? ['id' => $fetched['collection_id'], 'name' => $fetched['collection_name']]
            : null;

        $item = WatchlistItem::where('tmdb_id', $tmdbId)->first();
        if ($item) {
            $this->selectedMovie = [
                'tmdb_id' => $tmdbId,
                'title' => $item->title,
                'year' => $item->year,
                'poster_url' => $item->poster_url,
                'director' => $item->director,
                'actors' => $item->actors,
                'studio' => $item->studio ?: ($fetched['studio'] ?? null),
                ...$this->extraDetails($fetched),
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
            'tmdb_id' => $tmdbId,
            'title' => $fetched['title'] ?? $fallback['title'] ?? '',
            'year' => $fetched['year'] ?? $fallback['year'] ?? null,
            'poster_url' => $fetched['poster_url'] ?? $fallback['poster_url'] ?? null,
            'director' => $fetched['director'] ?? $fallback['director'] ?? null,
            'actors' => $fetched['actors'] ?? $fallback['actors'] ?? null,
            'studio' => $fetched['studio'] ?? $fallback['studio'] ?? null,
            ...$this->extraDetails($fetched),
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

        $this->dispatch('toast', message: $newRating === null
            ? "Note de « {$item->title} » retirée."
            : "Note de « {$item->title} » : {$newRating}/5.");

        // Garde la modale ouverte synchronisée si elle affiche ce même film.
        if ($this->selectedMovie && ($this->selectedMovie['item_id'] ?? null) === $id) {
            $this->selectedMovie['personal_rating'] = $newRating;
        }
    }

    /**
     * Change le statut d'un film (à voir / vu / à revoir), y compris depuis la modale de détails
     * — d'où le fait que cette méthode vit dans ce trait partagé plutôt que dans le seul Dashboard.
     * `$firstWatchedSource` (cinéma/streaming) est optionnel : voir UpdateWatchlistItemStatus.
     */
    public function setStatus(int $id, string $status, UpdateWatchlistItemStatus $action, ?string $firstWatchedSource = null): void
    {
        $item = WatchlistItem::findOrFail($id);
        $changed = $item->status !== $status;

        $action->handle($item, $status, $firstWatchedSource);

        // Recliquer sur le statut déjà actif ne change rien : pas de notification.
        if ($changed) {
            $this->dispatch('toast', message: match ($status) {
                'to_watch' => "« {$item->title} » remis dans « À voir ».",
                'watched' => "« {$item->title} » marqué comme vu.",
                'to_rewatch' => "« {$item->title} » ajouté à « À revoir ».",
            });
        }

        // Garde la modale ouverte synchronisée si elle affiche ce même film (statut, source de la
        // 1ère fois, et nombre de fois vu, qui peut s'être incrémenté automatiquement).
        if ($this->selectedMovie && ($this->selectedMovie['item_id'] ?? null) === $id) {
            $item->refresh();
            $this->selectedMovie['status'] = $item->status;
            $this->selectedMovie['first_watched_source'] = $item->first_watched_source;
        }
    }

    /**
     * Correction manuelle du nombre de fois vu (le compteur s'incrémente sinon automatiquement à
     * chaque passage en "vu" — voir UpdateWatchlistItemStatus). Bornée à 0-999 pour éviter une
     * saisie farfelue depuis le champ numérique de la carte film.
     */
    public function setWatchCount(int $id, int $watchCount): void
    {
        $watchCount = max(0, min(999, $watchCount));

        $item = WatchlistItem::findOrFail($id);
        $item->update(['watch_count' => $watchCount]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showCast = false;
        $this->selectedMovie = null;
    }

    /** Ouvre le panneau « Casting complet » du film affiché dans la modale. */
    public function openCast(): void
    {
        if ($this->selectedMovie && ! empty($this->selectedMovie['tmdb_id'])) {
            $this->showCast = true;
        }
    }

    public function closeCast(): void
    {
        $this->showCast = false;
    }

    /**
     * Casting complet et équipe technique du film affiché (voir TmdbClient::credits()). Propriété
     * calculée plutôt que publique : elle n'est ainsi pas sérialisée dans l'état Livewire envoyé à
     * chaque requête, et TmdbClient la garde en cache. Vide tant que le panneau est fermé.
     *
     * @return array{cast: array<int, array{name: string, character: ?string, photo_url: ?string}>, crew: array<string, string>, total: int}
     */
    #[Computed]
    public function fullCredits(): array
    {
        if (! $this->showCast || empty($this->selectedMovie['tmdb_id'])) {
            return ['cast' => [], 'crew' => [], 'total' => 0];
        }

        return app(TmdbClient::class)->credits($this->selectedMovie['tmdb_id']);
    }

    /**
     * Informations complémentaires de la fiche TMDB affichées dans la modale (titre original, slogan,
     * date de sortie, pays, langue, budget, recettes, scénaristes). Ces champs ne sont pas
     * enregistrés avec le film dans la liste : ils viennent toujours de TMDB (fiche en cache 24 h)
     * et restent à null si TMDB ne répond pas.
     *
     * @param  array<string, mixed>|null  $fetched  Résultat de TmdbClient::find().
     * @return array<string, mixed>
     */
    private function extraDetails(?array $fetched): array
    {
        return [
            'original_title' => $fetched['original_title'] ?? null,
            'tagline' => $fetched['tagline'] ?? null,
            'release_date' => $fetched['release_date'] ?? null,
            'countries' => $fetched['countries'] ?? null,
            'original_language' => $fetched['original_language'] ?? null,
            'budget' => $fetched['budget'] ?? null,
            'revenue' => $fetched['revenue'] ?? null,
            'writers' => $fetched['writers'] ?? null,
        ];
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
