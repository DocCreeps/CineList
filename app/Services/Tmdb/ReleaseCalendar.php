<?php

namespace App\Services\Tmdb;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calendrier des sorties en salles françaises : sorties sur une plage de dates (page "À venir",
 * voir between()) et date de sortie salle de films précis (accueil, voir forMovies()). Le champ
 * `release_date` de "discover/movie" n'étant pas fiable pour la date française exacte, ces deux
 * usages vérifient systématiquement le vrai calendrier pays par pays de chaque film
 * (frenchTheatricalDates()).
 */
class ReleaseCalendar
{
    public function __construct(private TmdbConnector $connector, private MovieDetails $details) {}

    /**
     * Sorties en salles (exploitation limitée ou large) en France entre deux dates incluses, avec
     * un filtrage « France uniquement » (voir withVerifiedFrenchReleaseDates()). Triées par date de
     * sortie (la plus récente d'abord si $newestFirst), puis par popularité décroissante pour que
     * les gros films passent avant les sorties confidentielles d'un même jour.
     *
     * Une erreur n'est renvoyée que si TMDB est injoignable ou mal configuré : une période sans
     * aucune sortie renvoie simplement une liste vide, sans erreur.
     *
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function between(Carbon $from, Carbon $to, bool $newestFirst = false): array
    {
        if (! $this->connector->configured()) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }

        $start = $from->copy()->startOfDay()->toDateString();
        $end = $to->copy()->startOfDay()->toDateString();

        if ($start > $end) {
            return ['results' => [], 'error' => null];
        }

        // À incrémenter quand la forme des résultats mis en cache change. Un cache par plage ; la
        // page « À venir » l'appelle une semaine cinéma à la fois, la clé reste donc stable toute la semaine.
        $cacheKey = sprintf('tmdb.releases.v1.%s.%s.%s', $start, $end, $newestFirst ? 'desc' : 'asc');
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $movies = collect();
            // 20 films par page : largement assez pour une semaine, et garde-fou pour une plage plus longue.
            $maxPages = 15;

            for ($page = 1; $page <= $maxPages; $page++) {
                $response = $this->connector->client()->get('discover/movie', $this->connector->withAuth([
                    'language' => 'fr-FR',
                    'region' => 'FR',
                    // 2 = sortie en salles limitée, 3 = sortie en salles large
                    'with_release_type' => '2|3',
                    'sort_by' => $newestFirst ? 'release_date.desc' : 'release_date.asc',
                    'release_date.gte' => $start,
                    'release_date.lte' => $end,
                    'page' => $page,
                ]));

                if ($response->failed()) {
                    Log::warning('TMDB releases failed.', ['status' => $response->status(), 'body' => $response->body()]);

                    // Échec dès la première page : rien à afficher, et surtout rien à mettre en cache.
                    if ($page === 1) {
                        return ['results' => [], 'error' => 'Erreur TMDB.'];
                    }

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
                    'original_release_date' => $movie['original_release_date'] ?? null,
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path'] : null,
                    'plot' => $movie['overview'] ?? null,
                    'popularity' => (float) ($movie['popularity'] ?? 0),
                ];
            })->filter()->unique('tmdb_id')->sort(function ($a, $b) use ($newestFirst) {
                $byDate = $newestFirst
                    ? strcmp($b['release_date'], $a['release_date'])
                    : strcmp($a['release_date'], $b['release_date']);

                return $byDate !== 0 ? $byDate : $b['popularity'] <=> $a['popularity'];
            })->values()->all();

            Cache::put($cacheKey, $results, now()->addHours(12));

            return ['results' => $results, 'error' => null];
        } catch (\Exception $e) {
            Log::warning('TMDB releases failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    /**
     * Date de sortie en salles de films précis (typiquement ceux de la watchlist), sans passer par
     * une fenêtre de dates : between() ne couvre qu'une période donnée, si bien qu'un film annoncé
     * en dehors de cette fenêtre — ou déjà à l'affiche — n'y figurerait pas.
     *
     * Pour chaque film : la prochaine sortie salle française (types 2/3) ou, à défaut, la plus
     * récente déjà passée. Si TMDB ne connaît aucune sortie salle française, on retombe sur la
     * date de sortie générale du film, marquée `confirmed` = false (elle peut différer de la date
     * française). `release_date` vaut null quand TMDB n'en connaît aucune.
     *
     * @param  array<int, int|string>  $ids
     * @return array<string, array{release_date: ?string, confirmed: bool}>  Indexé par id TMDB (string).
     */
    public function forMovies(array $ids): array
    {
        $ids = array_values(array_unique(array_map('strval', $ids)));

        if ($ids === [] || ! $this->connector->configured()) {
            return [];
        }

        // Mis en cache par film (12 h) : les rechargements de l'accueil ne rappellent pas TMDB.
        $frenchDates = $this->frenchTheatricalDates($ids);
        $today = now()->toDateString();
        $results = [];

        foreach ($ids as $id) {
            $dates = collect($frenchDates[$id] ?? []);
            $frenchDate = $dates->first(fn ($date) => $date >= $today) ?? $dates->last();

            if ($frenchDate) {
                $results[$id] = ['release_date' => $frenchDate, 'confirmed' => true];

                continue;
            }

            // Aucune sortie salle française connue (film annoncé de loin, ou appel en échec) :
            // date générale de la fiche TMDB, déjà mise en cache par MovieDetails::find().
            $general = $this->details->find($id)['release_date'] ?? null;

            $results[$id] = ['release_date' => filled($general) ? $general : null, 'confirmed' => false];
        }

        return $results;
    }

    /**
     * Le champ `release_date` renvoyé par discover/movie pour chaque résultat n'est PAS la date
     * régionale ayant servi au filtrage — ce peut être la sortie d'origine ailleurs, souvent des
     * années plus tôt (par exemple un vieux film ressorti en salles en France après restauration).
     * Récupère donc le vrai calendrier pays par pays de chaque candidat
     * (`movie/{id}/release_dates`) et ne garde que les films ayant une réelle sortie française
     * (type 2/3) dans la fenêtre, en retenant cette date-là. La date d'origine de discover est
     * conservée dans `original_release_date` : elle permet de repérer une ressortie (film déjà
     * sorti depuis longtemps, donc probablement déjà disponible en streaming).
     *
     * @param  Collection<int, array<string, mixed>>  $movies
     * @return Collection<int, array<string, mixed>>
     */
    private function withVerifiedFrenchReleaseDates(Collection $movies, string $start, string $end): Collection
    {
        if ($movies->isEmpty()) {
            return $movies;
        }

        $frenchDates = $this->frenchTheatricalDates($movies->pluck('id')->filter()->unique()->values()->all());

        return $movies->map(function ($movie) use ($frenchDates, $start, $end) {
            // Appel en échec (absent de $frenchDates) ou aucune sortie française dans la fenêtre : écarté.
            $matchingDate = collect($frenchDates[$movie['id']] ?? [])
                ->filter(fn ($date) => $date >= $start && $date <= $end)
                ->first();

            if (! $matchingDate) {
                return null;
            }

            return [...$movie, 'release_date' => $matchingDate, 'original_release_date' => $movie['release_date'] ?? null];
        })->filter()->values();
    }

    /**
     * Dates (`Y-m-d`, triées) des sorties en salles françaises (types 2/3) de chaque film, via
     * `movie/{id}/release_dates`. Sur une plage de plusieurs mois, cela représente des centaines
     * d'appels : ils sont donc envoyés par lots (pour rester sous la limite de débit de TMDB) et
     * mis en cache par film, ce qui évite de tout refaire quand deux plages voisines partagent
     * des films ou quand la fenêtre glisse d'un jour à l'autre.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int|string, array<int, string>>  Indexé par id TMDB ; un film dont l'appel a échoué est absent.
     */
    private function frenchTheatricalDates(array $ids): array
    {
        $cacheKey = fn ($id) => 'tmdb.fr.release_dates.v1.'.$id;

        // Une seule lecture groupée du cache plutôt qu'une requête par film.
        $cached = Cache::many(array_map($cacheKey, $ids));

        $dates = [];
        $toFetch = [];
        $fresh = [];

        foreach ($ids as $id) {
            $hit = $cached[$cacheKey($id)] ?? null;

            if ($hit !== null) {
                $dates[$id] = $hit;
            } else {
                $toFetch[] = $id;
            }
        }

        foreach (array_chunk($toFetch, 30) as $chunk) {
            $poolResponses = Http::pool(function (Pool $pool) use ($chunk) {
                return collect($chunk)->map(
                    fn ($id) => $this->connector->authorize($pool->as((string) $id)->acceptJson())
                        ->get($this->connector->baseUrl()."movie/{$id}/release_dates", $this->connector->withAuth([]))
                )->all();
            });

            foreach ($chunk as $id) {
                $res = $poolResponses[$id] ?? null;

                // Un appel qui n'a pas abouti (connexion, 429…) donne une ConnectionException ou une
                // réponse en erreur plutôt qu'une Response valide : le film reste simplement absent.
                if (! $res instanceof Response || ! $res->ok()) {
                    continue;
                }

                $frenchEntry = collect($res->json('results'))->firstWhere('iso_3166_1', 'FR');

                $releaseDates = collect($frenchEntry['release_dates'] ?? [])
                    ->filter(fn ($release) => in_array($release['type'] ?? null, [2, 3], true))
                    ->map(fn ($release) => substr($release['release_date'] ?? '', 0, 10))
                    ->filter(fn ($date) => $date !== '')
                    ->sort()
                    ->values()
                    ->all();

                $dates[$id] = $releaseDates;
                $fresh[$cacheKey($id)] = $releaseDates;
            }
        }

        if ($fresh !== []) {
            Cache::putMany($fresh, now()->addHours(12));
        }

        return $dates;
    }
}
