<?php

namespace App\Services\Tmdb;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Recherche de films sur TMDB selon le mode choisi (titre, réalisateur, acteur ou studio) :
 * requête à l'API, mise en forme d'un résultat brut TMDB au format interne (normalize()), puis
 * enrichissement (réalisateur/acteurs/studio) via un pool de requêtes mises en cache par film
 * (withEnrichedDetails()).
 */
class MovieSearch
{
    public function __construct(private TmdbConnector $connector) {}

    /**
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function search(string $query, string $mode = 'title', ?int $minYear = null): array
    {
        if (! $this->connector->configured()) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }
        if (blank(trim($query))) {
            return ['results' => [], 'error' => null];
        }

        // À incrémenter quand la forme ou la logique des résultats mis en cache change, pour
        // ne jamais resservir une entrée périmée issue d'une version précédente de la méthode.
        $cacheVersion = 'v5';
        $cacheKey = 'tmdb.search.'.$cacheVersion.'.'.$mode.'.'.md5(strtolower(trim($query))).'.y'.($minYear ?? 'all');
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $movies = match (true) {
                $mode === 'title' => $this->byTitle($query),
                $mode === 'studio' => $this->byStudio($query),
                default => $this->byPerson($query, $mode),
            };

            // Une des sous-recherches a échoué : elle renvoie alors directement le résultat final.
            if (is_array($movies)) {
                return $movies;
            }

            $movies = $this->normalize($movies);

            // Applique le filtre sur l'année minimale
            if ($minYear) {
                $movies = $movies->filter(fn ($m) => $m['year'] !== null && $m['year'] >= $minYear);
            }

            // Tri par année décroissante, valeurs nulles en fin de liste. Pas de plafond
            // arbitraire ici : en mode 'title' TMDB limite déjà à ~20 résultats par page, mais une
            // recherche par acteur/réalisateur doit pouvoir renvoyer une filmographie complète.
            $movies = $movies->sortByDesc(fn ($m) => $m['year'] ?? -9999)->values();

            $results = $this->withEnrichedDetails($movies->all());

            Cache::put($cacheKey, $results, now()->addHours(6));

            return ['results' => $results, 'error' => empty($results) ? 'Aucun résultat.' : null];
        } catch (\Exception $e) {
            Log::warning('TMDB search failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    /**
     * @return Collection|array{results: array, error: ?string}
     */
    private function byTitle(string $query): Collection|array
    {
        $response = $this->connector->client()->get('search/movie', $this->connector->withAuth([
            'query' => trim($query),
            'language' => 'fr-FR',
            'page' => 1,
        ]));

        if ($response->failed()) {
            Log::warning('TMDB search failed.', ['status' => $response->status(), 'body' => $response->body()]);
            return ['results' => [], 'error' => 'Erreur TMDB.'];
        }

        return collect($response->json('results'));
    }

    /**
     * @return Collection|array{results: array, error: ?string}
     */
    private function byStudio(string $query): Collection|array
    {
        $companyResponse = $this->connector->client()->get('search/company', $this->connector->withAuth([
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
        $firstPage = $this->connector->client()->get('discover/movie', $this->connector->withAuth([
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
        $movies = collect($firstData['results'] ?? []);

        // Plafond large : même les studios prolifiques (Ghibli, Pixar…) tiennent en
        // quelques pages ; un studio qui dépasserait ce plafond est un cas limite qui ne
        // justifie pas de l'élargir.
        $maxPages = 10;
        $totalPages = min($firstData['total_pages'] ?? 1, $maxPages);

        if ($totalPages > 1) {
            $extraPages = range(2, $totalPages);
            $poolResponses = Http::pool(function (Pool $pool) use ($extraPages, $companyId) {
                return collect($extraPages)->map(
                    fn ($page) => $this->connector->authorize($pool->as($page)->acceptJson())
                        ->get($this->connector->baseUrl().'discover/movie', $this->connector->withAuth([
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

        return $movies;
    }

    /**
     * @return Collection|array{results: array, error: ?string}
     */
    private function byPerson(string $query, string $mode): Collection|array
    {
        $personResponse = $this->connector->client()->get('search/person', $this->connector->withAuth([
            'query' => trim($query),
            'language' => 'fr-FR',
            'page' => 1,
        ]));

        if ($personResponse->failed() || empty($personResponse->json('results'))) {
            return ['results' => [], 'error' => 'Personne non trouvée.'];
        }

        $personId = $personResponse->json('results.0.id');

        $creditsResponse = $this->connector->client()->get("person/{$personId}/movie_credits", $this->connector->withAuth([
            'language' => 'fr-FR',
        ]));

        if ($creditsResponse->failed()) {
            return ['results' => [], 'error' => 'Erreur lors de la récupération des films.'];
        }

        $moviesKey = $mode === 'director' ? 'crew' : 'cast';
        $movies = collect($creditsResponse->json($moviesKey, []));

        if ($mode === 'director') {
            $movies = $movies->filter(fn ($m) => ($m['job'] ?? '') === 'Director');
        }

        return $movies;
    }

    /** Uniformise le format de base d'un lot de résultats bruts TMDB et écarte les entrées invalides. */
    private function normalize(Collection $movies): Collection
    {
        return $movies->map(function ($movie) {
            if (empty($movie['id']) || empty($movie['title'])) return null;
            $year = isset($movie['release_date']) && $movie['release_date'] ? (int) substr($movie['release_date'], 0, 4) : null;
            $character = strtolower($movie['character'] ?? '');

            return [
                'tmdb_id' => (string) $movie['id'],
                'title' => $movie['title'],
                'year' => $year,
                'release_date' => $movie['release_date'] ?? null,
                'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path'] : null,
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
    }

    /**
     * Récupère les détails manquants (réalisateur, acteurs, studio) affichés sur les cartes. Mis en
     * cache par film (indépendamment du cache par requête ci-dessus, et bien plus longtemps puisque
     * le réalisateur/casting/studio d'un film ne changent jamais) : un film déjà croisé dans une
     * recherche passée — même sur une requête totalement différente — est servi instantanément au
     * lieu de rappeler TMDB. Seuls les vrais défauts de cache passent par le pool.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    private function withEnrichedDetails(array $results): array
    {
        if (count($results) === 0) {
            return $results;
        }

        $detailsCacheKey = fn (string $id) => 'tmdb.movie.details.v1.'.$id;

        $toFetch = [];
        foreach ($results as $i => $m) {
            $cachedDetails = Cache::get($detailsCacheKey($m['tmdb_id']));
            if ($cachedDetails !== null) {
                $results[$i] = [...$m, ...$cachedDetails];
            } else {
                $toFetch[] = $m;
            }
        }

        if (count($toFetch) === 0) {
            return $results;
        }

        $poolResponses = Http::pool(function (Pool $pool) use ($toFetch) {
            return collect($toFetch)->map(
                fn ($m) => $this->connector->authorize($pool->as($m['tmdb_id']))
                    ->get($this->connector->baseUrl()."movie/{$m['tmdb_id']}", $this->connector->withAuth([
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

        return $results;
    }
}
