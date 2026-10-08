<?php

namespace App\Services\Tmdb;

use App\Support\Tmdb\FrenchLocale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fiches de films TMDB : détails complets (find), casting complet (credits), films similaires
 * (similarFilms), films d'une saga (collectionFilms) et plateformes de streaming (watchProviders).
 * Chaque méthode gère elle-même son cache — les durées reflètent la fréquence de mise à jour réelle
 * des données TMDB (rating et casting quasi figés, sorties plus mouvantes).
 */
class MovieDetails
{
    public function __construct(private TmdbConnector $connector) {}

    /**
     * Fiche complète d'un film TMDB (détails, crédits, bande-annonce), mise en cache 24 h.
     * La clé de cache est versionnée : elle change chaque fois que de nouveaux champs sont ajoutés
     * à la fiche (v4 : titre original, slogan, pays, langue, budget, recettes, scénaristes ; v5 : `imdb_rating` renommé `tmdb_rating`).
     */
    /**
     * Un identifiant TMDB est un entier. Les méthodes ci-dessous l'insèrent dans le chemin de
     * l'URL et dans une clé de cache : certains appelants le reçoivent du navigateur (propriétés
     * publiques Livewire), un `../` ou un `/` permettrait d'appeler un autre endpoint TMDB avec
     * le jeton de l'application. Tout identifiant invalide est donc traité comme « introuvable ».
     */
    private function isValidId(string $tmdbId): bool
    {
        return $tmdbId !== '' && strlen($tmdbId) <= 10 && ctype_digit($tmdbId);
    }

    public function find(string $tmdbId): ?array
    {
        if (! $this->isValidId($tmdbId) || ! $this->connector->configured()) {
            return null;
        }

        return Cache::remember("tmdb.movie.v5.{$tmdbId}", now()->addDay(), function () use ($tmdbId) {
            try {
                $response = $this->connector->client()->get("movie/{$tmdbId}", $this->connector->withAuth([
                    'language' => 'fr-FR', // Force le français
                    'append_to_response' => 'credits,videos',
                ]));

                if ($response->failed()) return null;

                $data = $response->json();
                $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null;
                $actors = collect($data['credits']['cast'] ?? [])->take(3)->pluck('name')->implode(', ');
                $studio = collect($data['production_companies'] ?? [])->pluck('name')->implode(', ');
                $writers = collect($data['credits']['crew'] ?? [])
                    ->whereIn('job', ['Screenplay', 'Writer'])
                    ->pluck('name')
                    ->unique()
                    ->take(3)
                    ->implode(', ');
                $countries = collect($data['production_countries'] ?? [])
                    ->map(fn ($country) => FrenchLocale::regionName($country['iso_3166_1'] ?? '', $country['name'] ?? ''))
                    ->filter()
                    ->implode(', ');

                // La requête fr-FR ci-dessus ne renvoie que les vidéos étiquetées françaises ; si
                // le film n'en a aucune (fréquent pour les films anciens ou peu grand public), une
                // seconde requête sans restriction de langue permet quand même de proposer une
                // bande-annonce en version originale.
                $trailer = $this->pickTrailer($data['videos']['results'] ?? [], preferFrench: true);
                if (! $trailer) {
                    $videosResponse = $this->connector->client()->get("movie/{$tmdbId}/videos", $this->connector->withAuth([]));
                    if ($videosResponse->ok()) {
                        $trailer = $this->pickTrailer($videosResponse->json('results') ?? [], preferFrench: false);
                    }
                }

                return [
                    'tmdb_id' => (string) $data['id'],
                    'title' => $data['title'] ?? $data['original_title'],
                    'year' => isset($data['release_date']) ? (int) substr($data['release_date'], 0, 4) : null,
                    'release_date' => $data['release_date'] ?? null,
                    'poster_url' => isset($data['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$data['poster_path'] : null,
                    'type' => 'movie',
                    'genre' => collect($data['genres'] ?? [])->pluck('name')->implode(', '),
                    'director' => $director,
                    'actors' => $actors ?: null,
                    'studio' => $studio ?: null,
                    'runtime' => isset($data['runtime']) ? $data['runtime'].' min' : null,
                    'tmdb_rating' => $data['vote_average'] ?? null,
                    'plot' => $data['overview'] ?? null,
                    'trailer_key' => $trailer['key'] ?? null,
                    'trailer_lang' => $trailer['lang'] ?? null,
                    'collection_id' => $data['belongs_to_collection']['id'] ?? null,
                    'collection_name' => $data['belongs_to_collection']['name'] ?? null,
                    // Infos complémentaires de la modale de détails. Un budget ou des recettes à 0
                    // signifient « non renseigné » chez TMDB : on les remplace par null.
                    'original_title' => filled($data['original_title'] ?? null) && ($data['original_title'] ?? null) !== ($data['title'] ?? null)
                        ? $data['original_title']
                        : null,
                    'tagline' => filled($data['tagline'] ?? null) ? $data['tagline'] : null,
                    'countries' => $countries ?: null,
                    'original_language' => FrenchLocale::languageName($data['original_language'] ?? null, $data['spoken_languages'] ?? []),
                    'budget' => ($data['budget'] ?? 0) > 0 ? (int) $data['budget'] : null,
                    'revenue' => ($data['revenue'] ?? 0) > 0 ? (int) $data['revenue'] : null,
                    'writers' => $writers ?: null,
                ];
            } catch (\Exception $e) {
                Log::warning('TMDB movie details failed.', ['tmdb_id' => $tmdbId, 'message' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * Casting complet et équipe technique principale d'un film (endpoint `movie/{id}/credits`),
     * pour le panneau « Casting complet » de la modale de détails. Mis en cache 3 jours ; un échec
     * n'est pas mis en cache (on retentera au prochain clic).
     *
     * `cast` est limité aux 100 premiers rôles (ordre du générique) ; `total` donne le nombre réel.
     * `crew` associe un intitulé (« Réalisation », « Scénario »…) aux noms correspondants.
     *
     * @return array{cast: array<int, array{name: string, character: ?string, photo_url: ?string}>, crew: array<string, string>, total: int}
     */
    public function credits(string $tmdbId): array
    {
        $empty = ['cast' => [], 'crew' => [], 'total' => 0];
        if (! $this->isValidId($tmdbId) || ! $this->connector->configured()) return $empty;

        $credits = Cache::remember("tmdb.movie.credits.v1.{$tmdbId}", now()->addDays(3), function () use ($tmdbId) {
            try {
                $response = $this->connector->client()->get("movie/{$tmdbId}/credits", $this->connector->withAuth([
                    'language' => 'fr-FR',
                ]));

                if ($response->failed()) return null;

                $cast = collect($response->json('cast', []))->filter(fn ($person) => ! empty($person['name']))->sortBy('order')->values();

                // Intitulé affiché => métiers TMDB correspondants, dans l'ordre d'affichage.
                $crewJobs = [
                    'Réalisation' => ['Director'],
                    'Scénario' => ['Screenplay', 'Writer'],
                    'Photographie' => ['Director of Photography'],
                    'Montage' => ['Editor'],
                    'Musique' => ['Original Music Composer'],
                ];
                $crew = [];
                foreach ($crewJobs as $label => $jobs) {
                    $names = collect($response->json('crew', []))
                        ->whereIn('job', $jobs)
                        ->pluck('name')
                        ->filter()
                        ->unique()
                        ->take(4)
                        ->implode(', ');

                    if ($names !== '') $crew[$label] = $names;
                }

                return [
                    'cast' => $cast->take(100)->map(fn ($person) => [
                        'name' => $person['name'],
                        'character' => filled($person['character'] ?? null) ? $person['character'] : null,
                        'photo_url' => ! empty($person['profile_path']) ? 'https://image.tmdb.org/t/p/w185'.$person['profile_path'] : null,
                    ])->all(),
                    'crew' => $crew,
                    'total' => $cast->count(),
                ];
            } catch (\Exception $e) {
                Log::warning('TMDB movie credits failed.', ['tmdb_id' => $tmdbId, 'message' => $e->getMessage()]);

                return null;
            }
        });

        return $credits ?? $empty;
    }

    /**
     * Jusqu'à 6 films recommandés pour un film donné (endpoint "recommendations" de TMDB, en
     * général plus pertinent que "similar"), pour le bandeau "Films similaires" de la modale de
     * détails. Mis en cache 3 jours, les recommandations bougeant très peu d'un jour à l'autre.
     *
     * @return array<int, array{tmdb_id: string, title: string, year: ?int, poster_url: ?string}>
     */
    public function similarFilms(string $tmdbId): array
    {
        if (! $this->isValidId($tmdbId) || ! $this->connector->configured()) return [];

        return Cache::remember("tmdb.movie.similar.v1.{$tmdbId}", now()->addDays(3), function () use ($tmdbId) {
            try {
                $response = $this->connector->client()->get("movie/{$tmdbId}/recommendations", $this->connector->withAuth([
                    'language' => 'fr-FR',
                    'page' => 1,
                ]));

                if ($response->failed()) return [];

                return collect($response->json('results', []))
                    ->filter(fn ($m) => !empty($m['id']) && !empty($m['title']))
                    ->take(6)
                    ->map(fn ($m) => [
                        'tmdb_id' => (string) $m['id'],
                        'title' => $m['title'],
                        'year' => isset($m['release_date']) && $m['release_date'] ? (int) substr($m['release_date'], 0, 4) : null,
                        'poster_url' => isset($m['poster_path']) ? 'https://image.tmdb.org/t/p/w200'.$m['poster_path'] : null,
                    ])
                    ->values()
                    ->all();
            } catch (\Exception $e) {
                Log::warning('TMDB similar films failed.', ['tmdb_id' => $tmdbId, 'message' => $e->getMessage()]);

                return [];
            }
        });
    }

    /**
     * Tous les films d'une "collection" TMDB (une saga : Star Wars, Toy Story…), pour l'action
     * "+ Ajouter toute la saga". Mis en cache 3 jours.
     *
     * @return array<int, array{tmdb_id: string, title: string, release_date: ?string}>
     */
    public function collectionFilms(int $collectionId): array
    {
        if (! $this->connector->configured()) return [];

        return Cache::remember("tmdb.collection.v1.{$collectionId}", now()->addDays(3), function () use ($collectionId) {
            try {
                $response = $this->connector->client()->get("collection/{$collectionId}", $this->connector->withAuth([
                    'language' => 'fr-FR',
                ]));

                if ($response->failed()) return [];

                return collect($response->json('parts', []))
                    ->filter(fn ($m) => !empty($m['id']) && !empty($m['title']))
                    ->map(fn ($m) => [
                        'tmdb_id' => (string) $m['id'],
                        'title' => $m['title'],
                        'release_date' => $m['release_date'] ?? null,
                    ])
                    ->values()
                    ->all();
            } catch (\Exception $e) {
                Log::warning('TMDB collection films failed.', ['collection_id' => $collectionId, 'message' => $e->getMessage()]);

                return [];
            }
        });
    }

    /**
     * Plateformes "où regarder" pour la France (abonnement/location/achat), issues des données
     * JustWatch agrégées par TMDB. Mises en cache 3 jours, comme les autres appels par film.
     *
     * @return array{link: ?string, flatrate: array<int, array{name: string, logo_url: ?string}>, rent: array, buy: array}
     */
    public function watchProviders(string $tmdbId): array
    {
        $empty = ['link' => null, 'flatrate' => [], 'rent' => [], 'buy' => []];
        if (! $this->isValidId($tmdbId) || ! $this->connector->configured()) return $empty;

        return Cache::remember("tmdb.providers.v1.{$tmdbId}", now()->addDays(3), function () use ($tmdbId, $empty) {
            try {
                $response = $this->connector->client()->get("movie/{$tmdbId}/watch/providers", $this->connector->withAuth([]));
                if ($response->failed()) return $empty;

                $fr = $response->json('results.FR');
                if (! $fr) return $empty;

                $mapProviders = fn (array $providers) => collect($providers)
                    ->map(fn ($p) => [
                        'name' => $p['provider_name'],
                        'logo_url' => isset($p['logo_path']) ? 'https://image.tmdb.org/t/p/w92'.$p['logo_path'] : null,
                    ])
                    ->values()
                    ->all();

                return [
                    'link' => $fr['link'] ?? null,
                    'flatrate' => $mapProviders($fr['flatrate'] ?? []),
                    'rent' => $mapProviders($fr['rent'] ?? []),
                    'buy' => $mapProviders($fr['buy'] ?? []),
                ];
            } catch (\Exception $e) {
                Log::warning('TMDB watch providers failed.', ['tmdb_id' => $tmdbId, 'message' => $e->getMessage()]);

                return $empty;
            }
        });
    }

    /**
     * Choisit la meilleure bande-annonce dans une liste de vidéos TMDB : une vidéo YouTube de
     * type "Trailer" (à défaut, n'importe quelle vidéo YouTube), en privilégiant la version
     * française si $preferFrench vaut true — sinon simplement la première trouvée, ce passage
     * étant déjà le repli utilisé quand aucune version française n'est disponible.
     *
     * @param array<int, array<string, mixed>> $videos
     * @return array{key: string, lang: string}|null
     */
    private function pickTrailer(array $videos, bool $preferFrench): ?array
    {
        $videos = collect($videos)->filter(fn ($v) => ($v['site'] ?? null) === 'YouTube');
        if ($videos->isEmpty()) return null;

        $trailers = $videos->filter(fn ($v) => ($v['type'] ?? null) === 'Trailer');
        $pool = $trailers->isNotEmpty() ? $trailers : $videos;

        $pick = $preferFrench
            ? ($pool->firstWhere('iso_639_1', 'fr') ?? $pool->first())
            : $pool->first();

        if (! $pick) return null;

        return [
            'key' => $pick['key'],
            'lang' => ($pick['iso_639_1'] ?? null) === 'fr' ? 'VF' : 'VO',
        ];
    }
}
