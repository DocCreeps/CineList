<?php

namespace App\Livewire\Upcoming;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Page « À venir » : sorties en salles françaises, semaine par semaine.
 *
 *  - en haut, « Cette semaine » : la semaine cinéma en cours, toujours affichée, non navigable ;
 *  - en dessous, un explorateur qui montre une seule autre semaine à la fois, de 3 mois en
 *    arrière à 3 mois en avant (la semaine en cours en est exclue : elle est déjà au-dessus).
 *
 * Chaque semaine est une requête TMDB courte, mise en cache séparément : naviguer d'une semaine à
 * l'autre ne recharge jamais les autres. Récupérer 3 mois d'un coup représenterait au contraire
 * plusieurs centaines d'appels à froid.
 */
class Index extends Component
{
    use InteractsWithMovies;

    /** Étendue de la navigation, en mois, dans le futur comme dans le passé. */
    private const RANGE_MONTHS = 3;

    /**
     * Semaine affichée dans l'explorateur : date (Y-m-d) du mercredi qui l'ouvre. Reflétée dans
     * l'URL (?semaine=2026-09-23) pour pouvoir la partager ou la retrouver.
     */
    #[Url(as: 'semaine')]
    public string $week = '';

    /**
     * « Cette semaine » s'affiche d'abord seule ; l'explorateur se charge juste après (wire:init),
     * pour que la page apparaisse sans attendre une seconde requête TMDB.
     */
    public bool $browserReady = false;

    public function loadBrowser(): void
    {
        $this->browserReady = true;
    }

    public function previousWeek(): void
    {
        $this->shiftWeek(-1);
    }

    public function nextWeek(): void
    {
        $this->shiftWeek(1);
    }

    /**
     * Déclenché par le sélecteur « Aller à ». $week est une propriété publique, donc modifiable
     * côté client : resolveWeek() écarte toute valeur hors de la plage navigable.
     */
    public function updatedWeek(): void
    {
        $this->week = $this->resolveWeek()->toDateString();
    }

    /** Décale d'une semaine dans la liste navigable (qui saute la semaine en cours). */
    private function shiftWeek(int $delta): void
    {
        $weeks = array_keys($this->navigableWeeks());
        $index = array_search($this->resolveWeek()->toDateString(), $weeks, true);

        if ($index === false || ! isset($weeks[$index + $delta])) {
            return;
        }

        $this->week = $weeks[$index + $delta];
    }

    public function with(): array
    {
        $tmdb = app(TmdbClient::class);

        [$weekStart, $weekEnd] = $this->currentWeek();

        // La semaine en cours est toujours chargée : c'est le bloc fixe du haut.
        $current = $this->loadWeek($tmdb, $weekStart, $weekEnd);
        $visible = $current['movies'];

        $weeks = $this->navigableWeeks();
        $selected = $this->resolveWeek();
        $browsed = null;

        // Valeur absente (premier affichage) ou hors plage : on réaligne la propriété sur la
        // semaine réellement montrée, pour que le sélecteur et l'URL restent cohérents.
        $this->week = $selected->toDateString();

        if ($this->browserReady) {
            // Les semaines des extrémités sont tronquées à la limite des 3 mois, pour ne jamais
            // afficher une sortie en dehors de la plage annoncée.
            $browsed = $this->loadWeek(
                $tmdb,
                $selected->copy()->max($this->lookback()),
                $selected->copy()->addDays(6)->min($this->horizon()),
            );

            $visible = [...$visible, ...$browsed['movies']];
        }

        // $results = films actuellement affichés : c'est la source de repli du trait
        // InteractsWithMovies (détails, ajout) quand TMDB ne répond pas à un appel individuel. On
        // n'y garde que l'essentiel, pour ne pas alourdir chaque aller-retour Livewire (cette
        // propriété est renvoyée au navigateur à chaque fois).
        $keep = array_flip(['tmdb_id', 'title', 'year', 'release_date', 'poster_url']);
        $this->results = array_map(fn ($movie) => array_intersect_key($movie, $keep), $visible);

        // Films déjà présents dans la watchlist (quel que soit le statut), pour que la vue grise la
        // carte et remplace les boutons d'ajout par un badge de statut — comme sur la recherche.
        $inList = WatchlistItem::whereIn('tmdb_id', collect($visible)->pluck('tmdb_id'))
            ->pluck('status', 'tmdb_id');

        $keys = array_keys($weeks);
        $index = array_search($selected->toDateString(), $keys, true);

        // La navigation ne contient jamais la semaine en cours : toute semaine sélectionnée est
        // soit avant $weekStart, soit après $weekEnd. Le titre de section reflète laquelle.
        $isFuture = $selected->gt($weekEnd);

        return [
            'week' => $current,
            'weekRange' => $this->rangeLabel($weekStart, $weekEnd),
            'browsed' => $browsed,
            'browsedLabel' => $isFuture ? 'Prochainement au cinéma' : 'Sorties passées',
            'browsedTitle' => $this->weekTitle($selected),
            'browsedRange' => $this->rangeLabel($selected, $selected->copy()->addDays(6)),
            'weekOptions' => $this->weekOptions($weeks, $weekStart),
            'canGoPrevious' => $index !== false && isset($keys[$index - 1]),
            'canGoNext' => $index !== false && isset($keys[$index + 1]),
            'inList' => $inList,
        ];
    }

    /** Début et fin de la semaine cinéma en cours : les sorties françaises tombent le mercredi. */
    private function currentWeek(): array
    {
        $start = now()->startOfDay()->startOfWeek(Carbon::WEDNESDAY);

        return [$start, $start->copy()->addDays(6)];
    }

    private function horizon(): Carbon
    {
        return now()->startOfDay()->addMonthsNoOverflow(self::RANGE_MONTHS);
    }

    private function lookback(): Carbon
    {
        return now()->startOfDay()->subMonthsNoOverflow(self::RANGE_MONTHS);
    }

    /**
     * Toutes les semaines atteignables par l'explorateur, de la plus ancienne à la plus récente,
     * indexées par date de début (Y-m-d). La semaine en cours est volontairement absente : elle
     * est déjà affichée en haut de la page, et la navigation la saute.
     *
     * @return array<string, Carbon>
     */
    private function navigableWeeks(): array
    {
        [$current] = $this->currentWeek();
        $lookback = $this->lookback();
        $horizon = $this->horizon();

        $weeks = [];

        // En arrière : la semaine compte tant qu'elle se termine après la limite des 3 mois.
        for ($start = $current->copy()->subDays(7); $start->copy()->addDays(6)->gte($lookback); $start->subDays(7)) {
            $weeks[] = $start->copy();
        }

        $weeks = array_reverse($weeks);

        // En avant : la semaine compte tant qu'elle commence avant la limite des 3 mois.
        for ($start = $current->copy()->addDays(7); $start->lte($horizon); $start->addDays(7)) {
            $weeks[] = $start->copy();
        }

        return collect($weeks)->keyBy(fn (Carbon $start) => $start->toDateString())->all();
    }

    /**
     * Semaine réellement affichée dans l'explorateur : celle demandée si elle est navigable,
     * sinon la semaine suivante (valeur par défaut, et repli pour une valeur absente, mal formée,
     * hors plage — ou égale à la semaine en cours, qui n'appartient qu'au bloc du haut).
     */
    private function resolveWeek(): Carbon
    {
        $weeks = $this->navigableWeeks();

        if (isset($weeks[$this->week])) {
            return $weeks[$this->week]->copy();
        }

        [$current] = $this->currentWeek();
        $next = $current->copy()->addDays(7)->toDateString();

        return ($weeks[$next] ?? reset($weeks))->copy();
    }

    /**
     * Options du sélecteur « Aller à », séparées en semaines passées et à venir.
     *
     * @param  array<string, Carbon>  $weeks
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function weekOptions(array $weeks, Carbon $current): array
    {
        $options = ['Semaines passées' => [], 'Prochaines semaines' => []];

        foreach ($weeks as $value => $start) {
            $group = $start->lt($current) ? 'Semaines passées' : 'Prochaines semaines';

            $options[$group][] = [
                'value' => $value,
                'label' => ucfirst($this->rangeLabel($start, $start->copy()->addDays(6))),
            ];
        }

        return array_filter($options);
    }

    /**
     * Charge une semaine (une requête TMDB, mise en cache séparément).
     *
     * @return array{movies: array<int, array<string, mixed>>, total: int, error: ?string}
     */
    private function loadWeek(TmdbClient $tmdb, Carbon $from, Carbon $to): array
    {
        $response = $tmdb->releasesBetween($from, $to);

        return [
            'movies' => $response['error'] ? [] : $response['results'],
            'total' => $response['error'] ? 0 : count($response['results']),
            'error' => $response['error'],
        ];
    }

    /** « Semaine du 24 septembre » (avec l'année si elle diffère de l'année en cours). */
    private function weekTitle(Carbon $start): string
    {
        $start = $start->copy()->locale('fr');

        return 'Semaine du ' . $start->translatedFormat($start->year === now()->year ? 'j F' : 'j F Y');
    }

    /**
     * « du 16 au 22 septembre » / « du 30 septembre au 6 octobre ». Le français est forcé sur
     * l'instance plutôt que déduit de la locale de l'application (APP_LOCALE vaut « en » par défaut).
     */
    private function rangeLabel(Carbon $from, Carbon $to): string
    {
        $from = $from->copy()->locale('fr');
        $to = $to->copy()->locale('fr');

        if ($from->year !== $to->year) {
            return 'du ' . $from->translatedFormat('j F Y') . ' au ' . $to->translatedFormat('j F Y');
        }

        if ($from->month === $to->month) {
            return 'du ' . $from->day . ' au ' . $to->translatedFormat('j F');
        }

        return 'du ' . $from->translatedFormat('j F') . ' au ' . $to->translatedFormat('j F');
    }
}
