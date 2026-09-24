<?php

namespace App\Support\Movies;

use App\Models\WatchlistItem;
use Illuminate\Support\Collection;

/**
 * « Favoris » calculés sur un ensemble de films : réalisateur, studio, film préféré. Partagé par
 * la page Bilan (un utilisateur) et la vue d'ensemble admin (tous les membres).
 */
class Favorites
{
    /** Poids de la note et de l'engagement (vues + revoir, voir groupFilms()) dans le score « général » ; total = 1. */
    private const SCORE_WEIGHT_RATING = 0.6;

    private const SCORE_WEIGHT_ENGAGEMENT = 0.4;

    /** Nombre de notes fictives (à la moyenne générale) ajoutées à chaque film pour lisser sa note. */
    private const RATING_PRIOR_WEIGHT = 2;

    /** Note maximale que peut donner un membre. */
    private const RATING_SCALE_MAX = 5;

    /**
     * Nombre de films par studio, du plus fréquent au moins fréquent. Le champ `studio` liste toutes
     * les sociétés de production du film, séparées par des virgules : le film compte une fois pour
     * chacune d'elles.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<string, int>
     */
    public static function studios(Collection $items): Collection
    {
        return $items->pluck('studio')
            ->flatMap(fn ($studio) => array_map('trim', explode(',', (string) $studio)))
            ->filter()
            ->countBy()
            ->sortDesc();
    }

    /**
     * Nombre de films par réalisateur, du plus fréquent au moins fréquent.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<string, int>
     */
    public static function directors(Collection $items): Collection
    {
        return $items->pluck('director')
            ->map(fn ($director) => trim((string) $director))
            ->filter()
            ->countBy()
            ->sortDesc();
    }

    /**
     * Films préférés d'un utilisateur, du mieux noté au moins bien noté (départage : note TMDB,
     * puis visionnage le plus récent). Vide tant qu'aucun film n'est noté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<int, WatchlistItem>
     */
    public static function films(Collection $items, int $limit = 3): Collection
    {
        return $items->whereNotNull('personal_rating')
            ->sortByDesc(fn (WatchlistItem $item) => [
                (int) $item->personal_rating,
                (float) $item->imdb_rating,
                $item->watched_at?->timestamp ?? 0,
            ])
            ->take($limit)
            ->values();
    }

    /**
     * Film préféré d'un utilisateur : celui qu'il a le mieux noté (départage : note TMDB, puis
     * visionnage le plus récent). Null tant qu'aucun film n'est noté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     */
    public static function film(Collection $items): ?WatchlistItem
    {
        return self::films($items, 1)->first();
    }

    /**
     * Classements des films préférés de l'ensemble des membres, sous quatre angles (chaque
     * classement est vide quand aucun film ne s'y prête) :
     *  - `general` : note ET engagement combinés (voir SCORE_WEIGHT_RATING / SCORE_WEIGHT_ENGAGEMENT
     *    et groupFilms()) — l'« engagement » est le nombre de fois vu (`watch_count`, tous membres
     *    confondus), doublé si au moins un membre a marqué le film « à revoir ». Volontairement
     *    indépendant du nombre de membres qui ont ajouté le film (`adds`), pour que « Film préféré »
     *    ne soit pas un simple doublon du classement « Le plus ajouté » ;
     *  - `adds`    : les films ajoutés par le plus de membres, quel que soit leur statut ;
     *  - `rating`  : les films les mieux notés en moyenne, sans tenir compte des ajouts ;
     *  - `views`   : les films vus le plus grand nombre de fois (`watch_count`), sans tenir compte
     *    de la note — sert au slide « Le plus revu » du bilan personnel, ignoré côté communauté.
     *
     * Chaque classement contient au plus `$limit` films (3 par défaut : le n° 1 puis les deux suivants),
     * du meilleur au moins bon.
     *
     * `$items` doit contenir les films de tous les membres ET de tous les statuts (à voir, vu, à
     * revoir) : un ajout compte dès que le film est dans la liste d'un membre. Chaque film est
     * regroupé par identifiant TMDB, quel que soit le membre qui l'a ajouté ou noté.
     *
     * `$popularity` permet de remplacer la mesure par défaut du classement « adds » (nombre de
     * membres distincts) par une autre. `$addsMinimum` filtre le classement « adds » (mais pas
     * « general », « rating » ni « views ») aux films dont cette mesure atteint ce seuil ; 0 par
     * défaut ne filtre rien (comportement inchangé).
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @param  (callable(Collection<int, WatchlistItem>): int)|null  $popularity
     * @return array{general: Collection<int, array<string, mixed>>, adds: Collection<int, array<string, mixed>>, rating: Collection<int, array<string, mixed>>, views: Collection<int, array<string, mixed>>}
     */
    public static function filmsAcrossMembers(Collection $items, int $limit = 3, ?callable $popularity = null, int $addsMinimum = 0): array
    {
        $films = self::groupFilms($items, $popularity);

        return [
            'general' => $films
                ->sortByDesc(fn (array $film) => [$film['score'], $film['engagement'], $film['average'] ?? 0])
                ->take($limit)
                ->values(),
            'adds' => $films
                ->filter(fn (array $film) => $film['adds'] >= $addsMinimum)
                ->sortByDesc(fn (array $film) => [$film['adds'], $film['average'] ?? 0, $film['ratings']])
                ->take($limit)
                ->values(),
            // Sans note, un film ne peut pas prétendre au titre de « mieux noté ».
            'rating' => $films
                ->filter(fn (array $film) => $film['ratings'] > 0)
                ->sortByDesc(fn (array $film) => [$film['average'], $film['ratings'], $film['adds']])
                ->take($limit)
                ->values(),
            // Classement pur sur le nombre de fois vu (`watch_count`), indépendant de la note et
            // de l'angle "adds" — sert au slide "Le plus revu" du bilan personnel.
            'views' => $films
                ->filter(fn (array $film) => $film['views'] > 0)
                ->sortByDesc(fn (array $film) => [$film['views'], $film['average'] ?? 0])
                ->take($limit)
                ->values(),
        ];
    }

    /**
     * Films préférés d'un seul utilisateur (bilan personnel, ou détail d'un membre côté admin),
     * avec exactement la même formule de score « générale » que filmsAcrossMembers() (note lissée
     * + engagement, voir groupFilms()) : l'engagement (vues + revoir) a un sens même pour un seul
     * utilisateur, contrairement au nombre de membres (« adds », toujours 1 ici et donc ignoré,
     * y compris par le carrousel — voir favorite-films-carousel.blade.php).
     *
     * Le carrousel personnel affiche à la place l'angle « views », classement pur par nombre de
     * fois vu (sans le doublement « à revoir » de l'engagement).
     *
     * Contrairement au mode communauté, PAS de dédoublonnage ici : avec peu de films, le n° 1
     * « général » coïncide très souvent avec le mieux noté et/ou le plus revu, et dédoublonner
     * réduirait alors le carrousel à une seule diapositive (plus de défilement auto ni de
     * flèches/points, cf. favorite-films-carousel.blade.php `@if ($slides->count() > 1)`). On
     * préfère ici toujours proposer les 3 diapositives dès qu'un film s'y prête, quitte à répéter
     * le même n° 1 sous des angles différents.
     *
     * `$items` doit contenir tous les films vus et à revoir d'un seul utilisateur.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return array{general: Collection<int, array<string, mixed>>, adds: Collection<int, array<string, mixed>>, rating: Collection<int, array<string, mixed>>, views: Collection<int, array<string, mixed>>}
     */
    public static function filmsForUser(Collection $items, int $limit = 3): array
    {
        return self::filmsAcrossMembers($items, $limit);
    }

    /**
     * Évite d'afficher deux fois le même film n° 1 dans le carrousel des favoris :
     *  - si le n° 1 « général » est aussi en tête de TOUS les autres angles non vides, une seule
     *    diapositive suffit : la générale ;
     *  - si le n° 1 « général » coïncide avec au moins un autre angle (mais pas tous), on ne
     *    garde pas la diapositive générale (elle n'apporterait rien de plus).
     *
     * Générique sur le nombre d'angles fournis en plus de "general" (2 pour le mode communauté —
     * adds/rating —, ou general/rating/views pour le mode personnel — voir filmsForUser()). Seuls
     * les n° 1 sont comparés : une diapositive écartée l'est en entier (son classement devient
     * vide), une diapositive conservée garde son classement complet.
     *
     * @param  array<string, Collection<int, array<string, mixed>>>  $favorites  Doit contenir la clé "general", plus au moins un autre angle.
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public static function withoutDuplicates(array $favorites): array
    {
        $topGeneral = $favorites['general']->first();
        $otherKeys = array_diff(array_keys($favorites), ['general']);

        $same = fn (?array $a, ?array $b) => $a !== null && $b !== null && $a['tmdb_id'] === $b['tmdb_id'];

        $nonEmptyOtherKeys = collect($otherKeys)->filter(fn ($key) => $favorites[$key]->isNotEmpty());
        $matchingKeys = collect($otherKeys)->filter(fn ($key) => $same($topGeneral, $favorites[$key]->first()));

        // Le n° 1 général coïncide avec TOUS les autres angles non vides : une seule diapositive suffit.
        if ($nonEmptyOtherKeys->isNotEmpty() && $matchingKeys->count() === $nonEmptyOtherKeys->count()) {
            $result = ['general' => $favorites['general']];
            foreach ($otherKeys as $key) {
                $result[$key] = collect();
            }

            return $result;
        }

        // Le n° 1 général coïncide avec au moins un autre angle : la diapositive générale n'apporte rien de plus.
        if ($matchingKeys->isNotEmpty()) {
            $favorites['general'] = collect();
        }

        return $favorites;
    }

    /**
     * Une ligne par film (identifiant TMDB), avec ses ajouts, son engagement, ses notes et son
     * score général.
     *
     * Le score général (0 à 100) mélange deux critères, pondérés par les constantes SCORE_WEIGHT_* (60 % / 40 %) :
     *  - la note, « lissée » : un film noté 5/5 par un seul membre ne doit pas écraser un film noté
     *    4,8 par cinq membres. On ajoute à chaque film RATING_PRIOR_WEIGHT notes fictives égales à la
     *    moyenne de toutes les notes ; le poids de cette moyenne s'efface à mesure que les vraies
     *    notes s'accumulent. Un film sans note reçoit ainsi la note moyenne, ni bonus ni pénalité ;
     *  - l'engagement : nombre de fois vu (`watch_count`, tous membres confondus), doublé si au
     *    moins un membre a marqué le film « à revoir », rapporté au film le plus engageant.
     *    Volontairement distinct de `adds` (nombre de membres qui l'ont ajouté, quel que soit leur
     *    statut) : ce dernier n'alimente que le classement « Le plus ajouté », pas le score général
     *    — sinon « Film préféré » ferait doublon avec lui (voir filmsAcrossMembers()).
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @param  (callable(Collection<int, WatchlistItem>): int)|null  $popularity  Remplace la mesure
     *      par défaut du classement « adds » (nombre de membres distincts) — voir filmsAcrossMembers().
     * @return Collection<int, array<string, mixed>>
     */
    private static function groupFilms(Collection $items, ?callable $popularity = null): Collection
    {
        $globalAverage = $items->whereNotNull('personal_rating')->isNotEmpty()
            ? (float) $items->whereNotNull('personal_rating')->avg('personal_rating')
            : self::RATING_SCALE_MAX / 2;

        $films = $items->groupBy('tmdb_id')->map(function (Collection $group) use ($popularity) {
            $first = $group->first();
            $rated = $group->whereNotNull('personal_rating');
            $views = (int) $group->sum('watch_count');

            return [
                'tmdb_id' => (string) $first->tmdb_id,
                'title' => $first->title,
                'year' => $first->year,
                'poster_url' => $first->poster_url,
                // Nombre de membres distincts par défaut (un même membre ne peut pas « voter »
                // deux fois) ; remplaçable par $popularity. N'alimente QUE le classement « adds »
                // (« Le plus ajouté ») : voir filmsAcrossMembers().
                'adds' => $popularity ? $popularity($group) : $group->pluck('user_id')->unique()->count(),
                // Nombre de fois vu, tous membres du groupe confondus (en pratique un seul membre
                // pour filmsForUser) — alimente l'angle "views" (classement "Le plus revu").
                'views' => $views,
                // Vues doublées si le film est marqué « à revoir » par au moins un membre : alimente
                // le score général (voir plus haut), pas le classement « adds ».
                'engagement' => $views * ($group->where('status', 'to_rewatch')->isNotEmpty() ? 2 : 1),
                // Où ce film a été vu pour la première fois (voir filmsForUser côté carrousel) :
                // n'a de sens que pour un groupe à un seul film (un seul utilisateur).
                'first_watched_source' => $first->first_watched_source,
                'ratings' => $rated->count(),
                'average' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
                'rating_sum' => (float) $rated->sum('personal_rating'),
                // Répartition des ajouts par statut, pour l'affichage (« 2 vus · 1 à voir »).
                'statuses' => [
                    'to_watch' => $group->where('status', 'to_watch')->count(),
                    'watched' => $group->where('status', 'watched')->count(),
                    'to_rewatch' => $group->where('status', 'to_rewatch')->count(),
                ],
            ];
        });

        $maxEngagement = max(1, (int) $films->max('engagement'));

        return $films->map(function (array $film) use ($globalAverage, $maxEngagement) {
            $smoothedRating = ($film['rating_sum'] + self::RATING_PRIOR_WEIGHT * $globalAverage)
                / ($film['ratings'] + self::RATING_PRIOR_WEIGHT);

            $film['score'] = (int) round(100 * (
                self::SCORE_WEIGHT_RATING * $smoothedRating / self::RATING_SCALE_MAX
                + self::SCORE_WEIGHT_ENGAGEMENT * $film['engagement'] / $maxEngagement
            ));

            return $film;
        });
    }
}
