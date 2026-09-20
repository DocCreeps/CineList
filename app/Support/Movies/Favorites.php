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
    /** Poids de la note et de la popularité (nombre d'ajouts) dans le score « général » ; total = 1. */
    private const SCORE_WEIGHT_RATING = 0.6;

    private const SCORE_WEIGHT_POPULARITY = 0.4;

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
     * Film préféré d'un utilisateur : celui qu'il a le mieux noté (départage : note TMDB, puis
     * visionnage le plus récent). Null tant qu'aucun film n'est noté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     */
    public static function film(Collection $items): ?WatchlistItem
    {
        return $items->whereNotNull('personal_rating')
            ->sortByDesc(fn (WatchlistItem $item) => [
                (int) $item->personal_rating,
                (float) $item->imdb_rating,
                $item->watched_at?->timestamp ?? 0,
            ])
            ->first();
    }

    /**
     * Films préférés de l'ensemble des membres, sous trois angles (chaque entrée vaut null quand
     * aucun film ne s'y prête) :
     *  - `general` : note ET popularité combinées (voir SCORE_WEIGHT_RATING / SCORE_WEIGHT_POPULARITY) ;
     *  - `adds`    : le film ajouté par le plus de membres, quel que soit son statut ;
     *  - `rating`  : le film le mieux noté en moyenne, sans tenir compte des ajouts.
     *
     * `$items` doit contenir les films de tous les membres ET de tous les statuts (à voir, vu, à
     * revoir) : un ajout compte dès que le film est dans la liste d'un membre. Chaque film est
     * regroupé par identifiant TMDB, quel que soit le membre qui l'a ajouté ou noté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return array{general: ?array<string, mixed>, adds: ?array<string, mixed>, rating: ?array<string, mixed>}
     */
    public static function filmsAcrossMembers(Collection $items): array
    {
        $films = self::groupFilms($items);

        if ($films->isEmpty()) {
            return ['general' => null, 'adds' => null, 'rating' => null];
        }

        return [
            'general' => $films
                ->sortByDesc(fn (array $film) => [$film['score'], $film['adds'], $film['average'] ?? 0])
                ->first(),
            'adds' => $films
                ->sortByDesc(fn (array $film) => [$film['adds'], $film['average'] ?? 0, $film['ratings']])
                ->first(),
            // Sans note, un film ne peut pas prétendre au titre de « mieux noté ».
            'rating' => $films
                ->filter(fn (array $film) => $film['ratings'] > 0)
                ->sortByDesc(fn (array $film) => [$film['average'], $film['ratings'], $film['adds']])
                ->first(),
        ];
    }

    /**
     * Évite d'afficher deux fois le même film dans le carrousel des favoris :
     *  - si le film « général » est aussi en tête des ajouts ou des notes, on ne garde pas la
     *    diapositive générale (elle n'apporterait rien de plus) ;
     *  - si les trois angles désignent le même film, une seule diapositive suffit : la générale.
     *
     * @param  array{general: ?array<string, mixed>, adds: ?array<string, mixed>, rating: ?array<string, mixed>}  $favorites  Résultat de filmsAcrossMembers().
     * @return array{general: ?array<string, mixed>, adds: ?array<string, mixed>, rating: ?array<string, mixed>}
     */
    public static function withoutDuplicates(array $favorites): array
    {
        $same = fn (?array $a, ?array $b) => $a !== null && $b !== null && $a['tmdb_id'] === $b['tmdb_id'];

        $sameAsAdds = $same($favorites['general'], $favorites['adds']);
        $sameAsRating = $same($favorites['general'], $favorites['rating']);

        if ($sameAsAdds && ($sameAsRating || $favorites['rating'] === null)) {
            return ['general' => $favorites['general'], 'adds' => null, 'rating' => null];
        }

        if ($sameAsAdds || $sameAsRating) {
            $favorites['general'] = null;
        }

        return $favorites;
    }

    /**
     * Une ligne par film (identifiant TMDB), avec ses ajouts, ses notes et son score général.
     *
     * Le score général (0 à 100) mélange deux critères, pondérés par les constantes SCORE_WEIGHT_* (60 % / 40 %) :
     *  - la note, « lissée » : un film noté 5/5 par un seul membre ne doit pas écraser un film noté
     *    4,8 par cinq membres. On ajoute à chaque film RATING_PRIOR_WEIGHT notes fictives égales à la
     *    moyenne de toutes les notes ; le poids de cette moyenne s'efface à mesure que les vraies
     *    notes s'accumulent. Un film sans note reçoit ainsi la note moyenne, ni bonus ni pénalité ;
     *  - la popularité : nombre de membres qui l'ont ajouté, rapporté au film le plus ajouté.
     *
     * @param  Collection<int, WatchlistItem>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private static function groupFilms(Collection $items): Collection
    {
        $globalAverage = $items->whereNotNull('personal_rating')->isNotEmpty()
            ? (float) $items->whereNotNull('personal_rating')->avg('personal_rating')
            : self::RATING_SCALE_MAX / 2;

        $films = $items->groupBy('tmdb_id')->map(function (Collection $group) {
            $first = $group->first();
            $rated = $group->whereNotNull('personal_rating');

            return [
                'tmdb_id' => (string) $first->tmdb_id,
                'title' => $first->title,
                'year' => $first->year,
                'poster_url' => $first->poster_url,
                // Nombre de membres distincts : un même membre ne peut pas « voter » deux fois.
                'adds' => $group->pluck('user_id')->unique()->count(),
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

        $maxAdds = max(1, (int) $films->max('adds'));

        return $films->map(function (array $film) use ($globalAverage, $maxAdds) {
            $smoothedRating = ($film['rating_sum'] + self::RATING_PRIOR_WEIGHT * $globalAverage)
                / ($film['ratings'] + self::RATING_PRIOR_WEIGHT);

            $film['score'] = (int) round(100 * (
                self::SCORE_WEIGHT_RATING * $smoothedRating / self::RATING_SCALE_MAX
                + self::SCORE_WEIGHT_POPULARITY * $film['adds'] / $maxAdds
            ));

            return $film;
        });
    }
}
