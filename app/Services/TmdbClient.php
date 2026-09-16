<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbClient
{
    /**
     * Recherche de films sur TMDB selon le mode choisi (titre, réalisateur, acteur ou studio).
     *
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function searchFilms(string $query, string $mode = 'title', ?int $minYear = null): array
    {
        if (blank(config('services.tmdb.token'))) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }
        if (blank(trim($query))) {
            return ['results' => [], 'error' => null];
        }

        // À incrémenter quand la forme ou la logique des résultats mis en cache change, pour
        // ne jamais resservir une entrée périmée issue d'une version précédente de la méthode.
        $cacheVersion = 'v5';
        $cacheKey = 'tmdb.search.' . $cacheVersion . '.' . $mode . '.' . md5(strtolower(trim($query))) . '.y' . ($minYear ?? 'all');
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $movies = collect();

            if ($mode === 'title') {
                $response = $this->client()->get('search/movie', $this->withAuth([
                    'query' => trim($query),
                    'language' => 'fr-FR',
                    'page' => 1,
                ]));

                if ($response->failed()) {
                    Log::warning('TMDB search failed.', ['status' => $response->status(), 'body' => $response->body()]);
                    return ['results' => [], 'error' => 'Erreur TMDB.'];
                }

                $movies = collect($response->json('results'));
            } elseif ($mode === 'studio') {
                $companyResponse = $this->client()->get('search/company', $this->withAuth([
                    'query' => trim($query),
                    'page' => 1,
                ]));

                if ($companyResponse->failed() || empty($companyResponse->json('results'))) {
                    return ['results' => [], 'error' => 'Studio non trouvé.'];
                }

                $companyId = $companyResponse->json('results.0.id');

                // La première page indique combien de pages existent ; les suivantes (s'il y en a)
                // sont récupérées d'un coup via un pool plutôt qu'une par une, chaque page étant
                // indépendante des autres.
                $firstPage = $this->client()->get('discover/movie', $this->withAuth([
                    'language' => 'fr-FR',
                    'with_companies' => $companyId,
                    'sort_by' => 'primary_release_date.desc',
                    'page' => 1,
                ]));

                if ($firstPage->failed()) {
                    Log::warning('TMDB studio discover failed.', ['status' => $firstPage->status(), 'body' => $firstPage->body()]);
                    return ['results' => [], 'error' => 'Erreur TMDB.'];
                }

                $firstData = $firstPage->json();
                $movies = $movies->merge($firstData['results'] ?? []);

                // Plafond large : même les studios prolifiques (Ghibli, Pixar…) tiennent en
                // quelques pages ; un studio qui dépasserait ce plafond est un cas limite qui ne
                // justifie pas de l'élargir.
                $maxPages = 10;
                $totalPages = min($firstData['total_pages'] ?? 1, $maxPages);

                if ($totalPages > 1) {
                    $extraPages = range(2, $totalPages);
                    $poolResponses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($extraPages, $companyId) {
                        return collect($extraPages)->map(
                            fn($page) =>
                            $this->authorize($pool->as($page)->acceptJson())
                                ->get(config('services.tmdb.url') . 'discover/movie', $this->withAuth([
                                    'language' => 'fr-FR',
                                    'with_companies' => $companyId,
                                    'sort_by' => 'primary_release_date.desc',
                                    'page' => $page,
                                ]))
                        )->all();
                    });

                    foreach ($extraPages as $page) {
                        $res = $poolResponses[$page] ?? null;
                        if ($res && $res->ok()) {
                            $movies = $movies->merge($res->json('results') ?? []);
                        }
                    }
                }
            } else {
                $personResponse = $this->client()->get('search/person', $this->withAuth([
                    'query' => trim($query),
                    'language' => 'fr-FR',
                    'page' => 1,
                ]));

                if ($personResponse->failed() || empty($personResponse->json('results'))) {
                    return ['results' => [], 'error' => 'Personne non trouvée.'];
                }

                $personId = $personResponse->json('results.0.id');

                $creditsResponse = $this->client()->get("person/{$personId}/movie_credits", $this->withAuth([
                    'language' => 'fr-FR',
                ]));

                if ($creditsResponse->failed()) {
                    return ['results' => [], 'error' => 'Erreur lors de la récupération des films.'];
                }

                $moviesKey = $mode === 'director' ? 'crew' : 'cast';
                $movies = collect($creditsResponse->json($moviesKey, []));

                if ($mode === 'director') {
                    $movies = $movies->filter(fn($m) => ($m['job'] ?? '') === 'Director');
                }
            }

            // Uniformise le format de base des films et écarte les entrées invalides
            $movies = $movies->map(function ($movie) {
                if (empty($movie['id']) || empty($movie['title'])) return null;
                $year = isset($movie['release_date']) && $movie['release_date'] ? (int) substr($movie['release_date'], 0, 4) : null;
                $character = strtolower($movie['character'] ?? '');

                return [
                    'tmdb_id' => (string) $movie['id'],
                    'title' => $movie['title'],
                    'year' => $year,
                    'release_date' => $movie['release_date'] ?? null,
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : null,
                    'type' => 'movie',
                    'plot' => $movie['overview'] ?? null,
                    'imdb_rating' => !empty($movie['vote_average']) ? round($movie['vote_average'], 1) : null,
                    // TMDB n'a pas d'indicateur propre pour les rôles de doublage. Beaucoup de
                    // crédits voix le précisent directement ("Woody (voice)", "Narrator (voice)"),
                    // mais de nombreuses fiches éditées par la communauté l'omettent — un crédit sur
                    // un film classé Animation (genre id 16) est donc lui aussi traité comme du
                    // doublage, puisqu'on ne peut pas apparaître à l'écran dans un film d'animation.
                    'is_voice' => str_contains($character, 'voice')
                        || str_contains($character, 'narrat')
                        || in_array(16, $movie['genre_ids'] ?? [], true),
                ];
            })->filter()->unique('tmdb_id');

            // Applique le filtre sur l'année minimale
            if ($minYear) {
                $movies = $movies->filter(fn($m) => $m['year'] !== null && $m['year'] >= $minYear);
            }

            // Tri par année décroissante, valeurs nulles en fin de liste. Pas de plafond
            // arbitraire ici : en mode 'title' TMDB limite déjà à ~20 résultats par page, mais une
            // recherche par acteur/réalisateur doit pouvoir renvoyer une filmographie complète.
            $movies = $movies->sortByDesc(fn($m) => $m['year'] ?? -9999)->values();

            // Récupère les détails manquants (réalisateur, acteurs, studio) affichés sur les
            // cartes. Mis en cache par film (indépendamment du cache par requête ci-dessus, et
            // bien plus longtemps puisque le réalisateur/casting/studio d'un film ne changent
            // jamais) : un film déjà croisé dans une recherche passée — même sur une requête
            // totalement différente — est servi instantanément au lieu de rappeler TMDB. Seuls
            // les vrais défauts de cache passent par le pool.
            $results = $movies->all();
            if (count($results) > 0) {
                $detailsCacheKey = fn(string $id) => 'tmdb.movie.details.v1.' . $id;

                $toFetch = [];
                foreach ($results as $i => $m) {
                    $cachedDetails = Cache::get($detailsCacheKey($m['tmdb_id']));
                    if ($cachedDetails !== null) {
                        $results[$i] = [...$m, ...$cachedDetails];
                    } else {
                        $toFetch[] = $m;
                    }
                }

                if (count($toFetch) > 0) {
                    $poolResponses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($toFetch) {
                        return collect($toFetch)->map(
                            fn($m) =>
                            $this->authorize($pool->as($m['tmdb_id']))
                                ->get(config('services.tmdb.url') . "movie/{$m['tmdb_id']}", $this->withAuth([
                                    'language' => 'fr-FR',
                                    'append_to_response' => 'credits',
                                ]))
                        )->all();
                    });

                    foreach ($results as &$movie) {
                        $res = $poolResponses[$movie['tmdb_id']] ?? null;
                        if ($res && $res->ok()) {
                            $data = $res->json();
                            $details = [
                                'director' => collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null,
                                'actors' => collect($data['credits']['cast'] ?? [])->take(3)->pluck('name')->implode(', ') ?: null,
                                'studio' => collect($data['production_companies'] ?? [])->pluck('name')->implode(', ') ?: null,
                                'plot' => $data['overview'] ?: $movie['plot'],
                            ];
                            $movie = [...$movie, ...$details];
                            Cache::put($detailsCacheKey($movie['tmdb_id']), $details, now()->addDays(7));
                        }
                    }
                    unset($movie);
                }
            }

            Cache::put($cacheKey, $results, now()->addHours(6));

            return ['results' => $results, 'error' => empty($results) ? 'Aucun résultat.' : null];
        } catch (\Exception $e) {
            Log::warning('TMDB search failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    /**
     * Sorties en salles (exploitation limitée ou large) en France sur les deux prochains mois,
     * triées chronologiquement. Filtre sur `release_date` + `region=FR` plutôt que sur
     * `primary_release_date` (global, qui ignore la région) — voir withVerifiedFrenchReleaseDates().
     *
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function upcomingFilms(): array
    {
        if (blank(config('services.tmdb.token'))) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }

        $cacheKey = 'tmdb.upcoming.v3.' . now()->toDateString();
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $start = now()->toDateString();
            $end = now()->addMonths(2)->toDateString();

            $movies = collect();
            $maxPages = 6;

            for ($page = 1; $page <= $maxPages; $page++) {
                $response = $this->client()->get('discover/movie', $this->withAuth([
                    'language' => 'fr-FR',
                    'region' => 'FR',
                    // 2 = sortie en salles limitée, 3 = sortie en salles large
                    'with_release_type' => '2|3',
                    'sort_by' => 'release_date.asc',
                    'release_date.gte' => $start,
                    'release_date.lte' => $end,
                    'page' => $page,
                ]));

                if ($response->failed()) {
                    Log::warning('TMDB upcoming failed.', ['status' => $response->status(), 'body' => $response->body()]);
                    break;
                }

                $data = $response->json();
                $movies = $movies->merge($data['results'] ?? []);

                if ($page >= ($data['total_pages'] ?? 1)) {
                    break;
                }
            }

            $movies = $this->withVerifiedFrenchReleaseDates($movies->unique('id'), $start, $end);

            $results = $movies->map(function ($movie) {
                if (empty($movie['id']) || empty($movie['title']) || empty($movie['release_date'])) return null;

                return [
                    'tmdb_id' => (string) $movie['id'],
                    'title' => $movie['title'],
                    'year' => (int) substr($movie['release_date'], 0, 4),
                    'release_date' => $movie['release_date'],
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : null,
                    'plot' => $movie['overview'] ?? null,
                ];
            })->filter()->unique('tmdb_id')->sortBy('release_date')->values()->all();

            Cache::put($cacheKey, $results, now()->addHours(12));

            return ['results' => $results, 'error' => empty($results) ? 'Aucune sortie prévue sur cette période.' : null];
        } catch (\Exception $e) {
            Log::warning('TMDB upcoming failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    /**
     * Même filtrage France uniquement que upcomingFilms(), restreint à un seul mois calendaire,
     * pour la navigation mois par mois de la page "/a-venir". Pour le mois en cours, part
     * d'aujourd'hui plutôt que du 1er : les jours passés relèvent du tableau de bord, pas d'ici.
     *
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function releasesForMonth(int $year, int $month): array
    {
        if (blank(config('services.tmdb.token'))) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        if ($monthEnd->isPast()) {
            return ['results' => [], 'error' => 'Ce mois est déjà passé.'];
        }

        $start = $monthStart->isPast() ? now()->startOfDay() : $monthStart;

        $cacheKey = sprintf('tmdb.upcoming.month.v3.%04d-%02d.%s', $year, $month, now()->toDateString());
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $movies = collect();
            $maxPages = 6;

            for ($page = 1; $page <= $maxPages; $page++) {
                $response = $this->client()->get('discover/movie', $this->withAuth([
                    'language' => 'fr-FR',
                    'region' => 'FR',
                    // 2 = sortie en salles limitée, 3 = sortie en salles large
                    'with_release_type' => '2|3',
                    'sort_by' => 'release_date.asc',
                    'release_date.gte' => $start->toDateString(),
                    'release_date.lte' => $monthEnd->toDateString(),
                    'page' => $page,
                ]));

                if ($response->failed()) {
                    Log::warning('TMDB upcoming (month) failed.', ['status' => $response->status(), 'body' => $response->body()]);
                    break;
                }

                $data = $response->json();
                $movies = $movies->merge($data['results'] ?? []);

                if ($page >= ($data['total_pages'] ?? 1)) {
                    break;
                }
            }

            $movies = $this->withVerifiedFrenchReleaseDates($movies->unique('id'), $start->toDateString(), $monthEnd->toDateString());

            $results = $movies->map(function ($movie) {
                if (empty($movie['id']) || empty($movie['title']) || empty($movie['release_date'])) return null;

                return [
                    'tmdb_id' => (string) $movie['id'],
                    'title' => $movie['title'],
                    'year' => (int) substr($movie['release_date'], 0, 4),
                    'release_date' => $movie['release_date'],
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : null,
                    'plot' => $movie['overview'] ?? null,
                ];
            })->filter()->unique('tmdb_id')->sortBy('release_date')->values()->all();

            Cache::put($cacheKey, $results, now()->addHours(12));

            return ['results' => $results, 'error' => empty($results) ? 'Aucune sortie prévue sur ce mois.' : null];
        } catch (\Exception $e) {
            Log::warning('TMDB upcoming (month) failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    /** Fiche complète d'un film TMDB (détails, crédits, bande-annonce), mise en cache 24 h. */
    public function find(string $tmdbId): ?array
    {
        if (blank(config('services.tmdb.token'))) return null;

        return Cache::remember("tmdb.movie.v3.{$tmdbId}", now()->addDay(), function () use ($tmdbId) {
            try {
                $response = $this->client()->get("movie/{$tmdbId}", $this->withAuth([
                    'language' => 'fr-FR', // Force le français
                    'append_to_response' => 'credits,videos',
                ]));

                if ($response->failed()) return null;

                $data = $response->json();
                $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null;
                $actors = collect($data['credits']['cast'] ?? [])->take(3)->pluck('name')->implode(', ');
                $studio = collect($data['production_companies'] ?? [])->pluck('name')->implode(', ');

                // La requête fr-FR ci-dessus ne renvoie que les vidéos étiquetées françaises ; si
                // le film n'en a aucune (fréquent pour les films anciens ou peu grand public), une
                // seconde requête sans restriction de langue permet quand même de proposer une
                // bande-annonce en version originale.
                $trailer = $this->pickTrailer($data['videos']['results'] ?? [], preferFrench: true);
                if (! $trailer) {
                    $videosResponse = $this->client()->get("movie/{$tmdbId}/videos", $this->withAuth([]));
                    if ($videosResponse->ok()) {
                        $trailer = $this->pickTrailer($videosResponse->json('results') ?? [], preferFrench: false);
                    }
                }

                return [
                    'tmdb_id' => (string) $data['id'],
                    'title' => $data['title'] ?? $data['original_title'],
                    'year' => isset($data['release_date']) ? (int) substr($data['release_date'], 0, 4) : null,
                    'release_date' => $data['release_date'] ?? null,
                    'poster_url' => isset($data['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : null,
                    'type' => 'movie',
                    'genre' => collect($data['genres'] ?? [])->pluck('name')->implode(', '),
                    'director' => $director,
                    'actors' => $actors ?: null,
                    'studio' => $studio ?: null,
                    'runtime' => isset($data['runtime']) ? $data['runtime'] . ' min' : null,
                    'imdb_rating' => $data['vote_average'] ?? null,
                    'plot' => $data['overview'] ?? null,
                    'trailer_key' => $trailer['key'] ?? null,
                    'trailer_lang' => $trailer['lang'] ?? null,
                    'collection_id' => $data['belongs_to_collection']['id'] ?? null,
                    'collection_name' => $data['belongs_to_collection']['name'] ?? null,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });
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
        if (blank(config('services.tmdb.token'))) return [];

        return Cache::remember("tmdb.movie.similar.v1.{$tmdbId}", now()->addDays(3), function () use ($tmdbId) {
            try {
                $response = $this->client()->get("movie/{$tmdbId}/recommendations", $this->withAuth([
                    'language' => 'fr-FR',
                    'page' => 1,
                ]));

                if ($response->failed()) return [];

                return collect($response->json('results', []))
                    ->filter(fn($m) => !empty($m['id']) && !empty($m['title']))
                    ->take(6)
                    ->map(fn($m) => [
                        'tmdb_id' => (string) $m['id'],
                        'title' => $m['title'],
                        'year' => isset($m['release_date']) && $m['release_date'] ? (int) substr($m['release_date'], 0, 4) : null,
                        'poster_url' => isset($m['poster_path']) ? 'https://image.tmdb.org/t/p/w200' . $m['poster_path'] : null,
                    ])
                    ->values()
                    ->all();
            } catch (\Exception $e) {
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
        if (blank(config('services.tmdb.token'))) return [];

        return Cache::remember("tmdb.collection.v1.{$collectionId}", now()->addDays(3), function () use ($collectionId) {
            try {
                $response = $this->client()->get("collection/{$collectionId}", $this->withAuth([
                    'language' => 'fr-FR',
                ]));

                if ($response->failed()) return [];

                return collect($response->json('parts', []))
                    ->filter(fn($m) => !empty($m['id']) && !empty($m['title']))
                    ->map(fn($m) => [
                        'tmdb_id' => (string) $m['id'],
                        'title' => $m['title'],
                        'release_date' => $m['release_date'] ?? null,
                    ])
                    ->values()
                    ->all();
            } catch (\Exception $e) {
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
        if (blank(config('services.tmdb.token'))) return $empty;

        return Cache::remember("tmdb.providers.v1.{$tmdbId}", now()->addDays(3), function () use ($tmdbId, $empty) {
            try {
                $response = $this->client()->get("movie/{$tmdbId}/watch/providers", $this->withAuth([]));
                if ($response->failed()) return $empty;

                $fr = $response->json('results.FR');
                if (! $fr) return $empty;

                $mapProviders = fn(array $providers) => collect($providers)
                    ->map(fn($p) => [
                        'name' => $p['provider_name'],
                        'logo_url' => isset($p['logo_path']) ? 'https://image.tmdb.org/t/p/w92' . $p['logo_path'] : null,
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
        $videos = collect($videos)->filter(fn($v) => ($v['site'] ?? null) === 'YouTube');
        if ($videos->isEmpty()) return null;

        $trailers = $videos->filter(fn($v) => ($v['type'] ?? null) === 'Trailer');
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

    /**
     * Le champ `release_date` renvoyé par discover/movie pour chaque résultat n'est PAS la date
     * régionale ayant servi au filtrage — ce peut être la sortie d'origine ailleurs, souvent des
     * années plus tôt (par exemple un vieux film ressorti en salles en France après restauration).
     * Récupère donc le vrai calendrier pays par pays de chaque candidat
     * (`movie/{id}/release_dates`) et ne garde que les films ayant une réelle sortie française
     * (type 2/3) dans la fenêtre, en retenant cette date-là.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $movies
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function withVerifiedFrenchReleaseDates(\Illuminate\Support\Collection $movies, string $start, string $end): \Illuminate\Support\Collection
    {
        if ($movies->isEmpty()) {
            return $movies;
        }

        $ids = $movies->pluck('id')->filter()->unique()->values();

        $poolResponses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($ids) {
            return $ids->map(
                fn ($id) => $this->authorize($pool->as($id)->acceptJson())
                    ->get(config('services.tmdb.url')."movie/{$id}/release_dates", $this->withAuth([]))
            )->all();
        });

        return $movies->map(function ($movie) use ($poolResponses, $start, $end) {
            $res = $poolResponses[$movie['id']] ?? null;

            if (! $res || ! $res->ok()) {
                return null;
            }

            $frenchEntry = collect($res->json('results'))->firstWhere('iso_3166_1', 'FR');

            $matchingDate = collect($frenchEntry['release_dates'] ?? [])
                ->filter(fn ($release) => in_array($release['type'] ?? null, [2, 3], true))
                ->map(fn ($release) => substr($release['release_date'] ?? '', 0, 10))
                ->filter(fn ($date) => $date !== '' && $date >= $start && $date <= $end)
                ->sort()
                ->first();

            if (! $matchingDate) {
                return null;
            }

            return [...$movie, 'release_date' => $matchingDate];
        })->filter()->values();
    }

    /**
     * Client HTTP de base pour TMDB, pointé sur l'URL configurée, avec le jeton Bearer v4
     * attaché quand l'identifiant configuré en est un.
     */
    private function client(): PendingRequest
    {
        return $this->authorize(Http::baseUrl(config('services.tmdb.url'))->acceptJson());
    }

    /**
     * Ajoute l'authentification Bearer à une requête, mais uniquement quand l'identifiant
     * configuré est un "API Read Access Token" v4 (un JWT). Une clé d'API v3 classique n'est pas
     * un Bearer valide et doit être passée en paramètre d'URL à la place (voir withAuth()).
     */
    private function authorize(PendingRequest $request): PendingRequest
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $request->withToken($token) : $request;
    }

    /**
     * Ajoute l'identifiant TMDB à la chaîne de requête quand une clé d'API v3 est configurée.
     * Les jetons Bearer v4 passent par un en-tête (voir authorize()) : dans ce cas, rien n'est
     * ajouté à l'URL.
     */
    private function withAuth(array $query): array
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $query : [...$query, 'api_key' => $token];
    }

    /**
     * L'"API Read Access Token" v4 de TMDB est un JWT (trois segments séparés par des points).
     * L'ancienne clé d'API v3 est une simple chaîne de 32 caractères et ne doit jamais être
     * envoyée comme jeton Bearer — TMDB la rejette avec une 401.
     */
    private function isV4Token(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }
}
