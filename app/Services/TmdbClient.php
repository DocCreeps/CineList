<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbClient
{
    /** @return array{results: array<int, array<string, mixed>>, error: ?string} */
    public function searchFilms(string $query, string $mode = 'title', ?int $minYear = null): array
    {
        if (blank(config('services.tmdb.token'))) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }
        if (blank(trim($query))) {
            return ['results' => [], 'error' => null];
        }

        // Bump when the shape/logic of the cached results changes, so stale
        // entries from a previous version of this method never get served.
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

                // First page tells us how many pages exist; fetch the rest (if any) all at
                // once via pool instead of one-by-one, since each page is independent.
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

                // Capped generously since even prolific studios (Ghibli, Pixar…) fit within a
                // few pages; a studio with more than this many pages is an edge case not worth
                // widening the cap for.
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

            // Standardize basic movie format and filter out invalid ones
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
                    // TMDB has no clean "dubbing role" flag. Many voice credits do say
                    // so directly ("Woody (voice)", "Narrator (voice)"), but a lot of
                    // community-edited entries omit it — so a credit on a movie tagged
                    // Animation (genre id 16) is treated as a dubbing role too, since
                    // there's no such thing as being on-screen in an animated film.
                    'is_voice' => str_contains($character, 'voice')
                        || str_contains($character, 'narrat')
                        || in_array(16, $movie['genre_ids'] ?? [], true),
                ];
            })->filter()->unique('tmdb_id');

            // Apply min year filter
            if ($minYear) {
                $movies = $movies->filter(fn($m) => $m['year'] !== null && $m['year'] >= $minYear);
            }

            // Sort by year desc, nulls at bottom. No arbitrary cap here: for
            // 'title' TMDB already limits to ~20 results per page, but an
            // actor/director search must be able to return a full filmography.
            $movies = $movies->sortByDesc(fn($m) => $m['year'] ?? -9999)->values();

            // Fetch missing details (director, actors, studio) to show on cards. Cached per
            // movie (independent of the per-query cache above and much longer-lived, since a
            // movie's director/cast/studio never change) so a movie already seen in any past
            // search — even under a totally different query — is served instantly here instead
            // of re-hitting TMDB. Only genuine cache misses go through the pool.
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
     * Cinema releases (limited or wide theatrical) in France over the next two
     * months, sorted chronologically. Paginates through TMDB's discover
     * endpoint, capped at a handful of pages since two months of releases
     * fit comfortably within that.
     *
     * Uses `release_date.gte/lte` + `region=FR` rather than `primary_release_date.*`:
     * the latter filters on a movie's global primary release date regardless of region, which
     * lets through films whose *French* release falls outside the window (or outside France
     * entirely) as long as their worldwide primary date matches.
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
                    // 2 = limited theatrical, 3 = wide theatrical
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
     * Cinema releases (limited or wide theatrical) in France for a single calendar month, used
     * by the "/a-venir" page's month-by-month navigation (calendrier). Same France-only
     * filtering as upcomingFilms() — region=FR + with_release_type 2|3 + release_date.gte/lte
     * (regionalized, unlike primary_release_date which is global) — but scoped to one
     * month instead of a fixed 2-month window, so the page can be browsed further ahead.
     *
     * For the current month, starts from today rather than the 1st: days already past are
     * already "sorti" and belong on the dashboard, not the upcoming page.
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
                    // 2 = limited theatrical, 3 = wide theatrical
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

    public function find(string $tmdbId): ?array
    {
        if (blank(config('services.tmdb.token'))) return null;

        return Cache::remember("tmdb.movie.v3.{$tmdbId}", now()->addDay(), function () use ($tmdbId) {
            try {
                $response = $this->client()->get("movie/{$tmdbId}", $this->withAuth([
                    'language' => 'fr-FR', // Ensure French
                    'append_to_response' => 'credits,videos',
                ]));

                if ($response->failed()) return null;

                $data = $response->json();
                $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null;
                $actors = collect($data['credits']['cast'] ?? [])->take(3)->pluck('name')->implode(', ');
                $studio = collect($data['production_companies'] ?? [])->pluck('name')->implode(', ');

                // The fr-FR request above only returns videos tagged as French; if the movie
                // has none (common for older or less mainstream films), fall back to a second,
                // unscoped request to still offer an (original-language) trailer.
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
     * Up to 6 recommended movies for a given film (TMDB's "recommendations" endpoint, which
     * tends to be more relevant than "similar"), for the "Films similaires" strip in the
     * details modal. Cached for 3 days since recommendations barely change day to day.
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
     * All films in a TMDB "collection" (a saga: e.g. Star Wars, Toy Story), for the
     * "+ Ajouter toute la saga" action. Cached for 3 days.
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
     * "Where to watch" providers for France (flatrate/rent/buy), from TMDB's own aggregated
     * JustWatch data. Cached for 3 days like the other per-film lookups.
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
     * Picks the best trailer from a TMDB videos list: a YouTube "Trailer" (falling back to any
     * YouTube video if no official trailer exists), preferring French audio when $preferFrench
     * is true — otherwise just the first match, since this is already the no-French-available
     * fallback pass.
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
     * Base HTTP client for TMDB, pointed at the configured base URL, with the
     * v4 Bearer token attached when the configured credential is one.
     */
    /**
     * TMDB's discover/movie endpoint DOES filter server-side by the requested region's release
     * dates when `region` + `release_date.gte/lte` + `with_release_type` are combined — but the
     * `release_date` field it returns on each result is NOT that region-specific date. It can
     * instead be the film's original release somewhere else, often years earlier: this is
     * exactly what makes an old film (e.g. a 2002 title getting a restored/anniversary
     * re-release in French cinemas) show up in the results with its original release date
     * instead of the upcoming French one.
     *
     * Rather than trust that field, this fetches each candidate's real per-country release
     * calendar (`movie/{id}/release_dates`) and keeps only films that actually have a France
     * release of the requested type (2 = limited, 3 = wide theatrical) inside the requested
     * window, using that exact date for sorting/display/grouping instead of the unreliable one.
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

    private function client(): PendingRequest
    {
        return $this->authorize(Http::baseUrl(config('services.tmdb.url'))->acceptJson());
    }

    /**
     * Attach Bearer auth to a pending request, but only when the configured
     * credential is a v4 "API Read Access Token" (a JWT). A classic v3 API
     * key is not a valid Bearer token and must be sent as a query param
     * instead (see withAuth()).
     */
    private function authorize(PendingRequest $request): PendingRequest
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $request->withToken($token) : $request;
    }

    /**
     * Adds the TMDB credential to the query string when a v3 API key is
     * configured. v4 Bearer tokens are sent as a header instead (see
     * authorize()), so nothing is added to the query in that case.
     */
    private function withAuth(array $query): array
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $query : [...$query, 'api_key' => $token];
    }

    /**
     * TMDB's v4 "API Read Access Token" is a JWT (three dot-separated
     * segments). The older v3 API key is a plain 32-character string and
     * must never be sent as a Bearer token — TMDB rejects it with a 401.
     */
    private function isV4Token(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }
}
