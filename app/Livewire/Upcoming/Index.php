<?php

namespace App\Livewire\Upcoming;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Services\TmdbClient;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Index extends Component
{
    use InteractsWithMovies;

    public ?string $loadError = null;

    /** Mois affiché, au format "YYYY-MM". Piloté par les flèches précédent/suivant et le sélecteur calendrier. */
    public string $period = '';

    /** Jusqu'à combien de mois dans le futur la navigation est autorisée. */
    private const MAX_MONTHS_AHEAD = 11;

    private const MONTHS_FR = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function mount(): void
    {
        $this->period = now()->format('Y-m');
        $this->loadResults();
    }

    public function previousMonth(): void
    {
        $this->shiftMonth(-1);
    }

    public function nextMonth(): void
    {
        $this->shiftMonth(1);
    }

    /**
     * Déclenché par le sélecteur "calendrier" (liste déroulante des mois disponibles).
     * $period est une propriété publique Livewire, donc modifiable côté client avec une valeur
     * arbitraire : currentMonth() retombe sur le mois courant si le format est invalide, et on
     * revient aussi au mois courant si la valeur est syntaxiquement correcte mais hors de la
     * plage navigable (avant aujourd'hui ou trop loin dans le futur).
     */
    public function updatedPeriod(): void
    {
        $date = $this->currentMonth();

        if (! $this->isNavigable($date)) {
            $this->period = now()->format('Y-m');
        }

        $this->loadResults();
    }

    private function shiftMonth(int $delta): void
    {
        $date = $this->currentMonth()->addMonthsNoOverflow($delta);

        if (! $this->isNavigable($date)) {
            return;
        }

        $this->period = $date->format('Y-m');
        $this->loadResults();
    }

    private function isNavigable(Carbon $date): bool
    {
        return $date->gte($this->minMonth()) && $date->lte($this->maxMonth());
    }

    private function currentMonth(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();
        } catch (\Exception) {
            // $period ne respecte pas le format "YYYY-MM" (valeur corrompue ou forgée côté
            // client) : on retombe sur le mois courant plutôt que de laisser planter la page.
            $this->period = now()->format('Y-m');

            return now()->startOfMonth();
        }
    }

    private function minMonth(): Carbon
    {
        return now()->startOfMonth();
    }

    private function maxMonth(): Carbon
    {
        return $this->minMonth()->copy()->addMonths(self::MAX_MONTHS_AHEAD);
    }

    private function loadResults(): void
    {
        $date = $this->currentMonth();
        $response = app(TmdbClient::class)->releasesForMonth($date->year, $date->month);
        $this->results = $response['results'];
        $this->loadError = $response['error'];
    }

    /** Regroupe le mois affiché en tranches hebdomadaires, pour une lecture plus digeste qu'une longue liste plate. */
    public function with(): array
    {
        $date = $this->currentMonth();

        $groups = collect($this->results)->groupBy(
            fn ($movie) => 'Semaine du ' . Carbon::parse($movie['release_date'])->startOfWeek()->format('d/m')
        );

        $monthOptions = collect(range(0, self::MAX_MONTHS_AHEAD))
            ->map(function ($i) {
                $d = $this->minMonth()->copy()->addMonthsNoOverflow($i);

                return ['value' => $d->format('Y-m'), 'label' => self::MONTHS_FR[$d->month] . ' ' . $d->year];
            });

        return [
            'groups' => $groups,
            'monthLabel' => self::MONTHS_FR[$date->month] . ' ' . $date->year,
            'monthOptions' => $monthOptions,
            'canGoPrevious' => $date->gt($this->minMonth()),
            'canGoNext' => $date->lt($this->maxMonth()),
        ];
    }
}
