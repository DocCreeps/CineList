<?php

namespace App\Livewire\Watchlist;

use App\Actions\Stats\ComputeCommunityStats;
use App\Actions\Watchlist\BulkUpdateWatchlistStatus;
use App\Actions\Watchlist\FilterWatchlistItems;
use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use Livewire\Component;

class Dashboard extends Component
{
    use InteractsWithMovies;

    /** @var array<int, string> Vide = "pas de filtre" (tous les statuts). */
    public array $statusFilter = [];

    /** @var array<int, string> Vide = "pas de filtre" (toutes les sources). */
    public array $sourceFilter = [];

    /** Filtres à valeur unique (ensembles de valeurs ouverts, donc une liste déroulante plutôt que des puces). */
    public string $genreFilter = '';
    public string $directorFilter = '';
    public string $studioFilter = '';

    public string $sortBy = 'priority';

    /** Indique si la section "déjà vus" est dépliée (repliée/masquée par défaut). */
    public bool $showWatched = false;

    /** Bornes facultatives sur l'année de sortie, même principe que le minYear de la page de recherche. */
    public ?int $minYear = null;
    public ?int $maxYear = null;

    /** Recherche sur le titre ou la note personnelle (sous-chaîne, insensible à la casse). */
    public string $searchQuery = '';

    /** Si vrai, restreint la grille aux films "à voir" ajoutés il y a plus de 3 mois. */
    public bool $staleOnly = false;

    /** @var array<int, int> Identifiants cochés dans la grille, pour la barre d'actions groupées. */
    public array $selectedIds = [];

    public function toggleShowWatched(): void
    {
        $this->showWatched = ! $this->showWatched;
    }

    public function toggleStale(): void
    {
        $this->staleOnly = ! $this->staleOnly;
    }

    public function toggleSelect(int $id): void
    {
        $this->selectedIds = in_array($id, $this->selectedIds, true)
            ? array_values(array_diff($this->selectedIds, [$id]))
            : [...$this->selectedIds, $id];
    }

    /**
     * Ajoute à la sélection tous les films actuellement affichés (appelé depuis la vue avec les
     * identifiants visibles). Fonctionne en bascule : si tous les films visibles sont déjà
     * sélectionnés, il les désélectionne, ce qui permet aussi de vider la sélection courante.
     */
    public function selectAllVisible(int ...$ids): void
    {
        $allVisibleAlreadySelected = ! empty($ids) && empty(array_diff($ids, $this->selectedIds));

        $this->selectedIds = $allVisibleAlreadySelected
            ? array_values(array_diff($this->selectedIds, $ids))
            : array_values(array_unique([...$this->selectedIds, ...$ids]));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    /**
     * Applique à chaque film sélectionné la même logique de date de visionnage que setStatus(),
     * en une seule transaction (voir BulkUpdateWatchlistStatus).
     */
    public function bulkSetStatus(string $status, BulkUpdateWatchlistStatus $action): void
    {
        $newStatus = WatchStatus::tryFrom($status);
        abort_unless($newStatus !== null, 422);

        $done = $action->handle($this->selectedIds, $status);

        $this->clearSelection();

        if ($done > 0) {
            $s = $done > 1 ? 's' : '';

            $this->dispatch('toast', message: match ($newStatus) {
                WatchStatus::ToWatch => "{$this->filmCount($done)} remis dans « À voir ».",
                WatchStatus::Watched => "{$this->filmCount($done)} marqué{$s} comme vu{$s}.",
                WatchStatus::ToRewatch => "{$this->filmCount($done)} ajouté{$s} à « À revoir ».",
            });
        }
    }

    public function bulkSetPriority(int $priority): void
    {
        abort_unless(in_array($priority, [1, 2, 3], true), 422);

        // Compté avant la mise à jour : selon le moteur SQL, update() ne renvoie parfois que les
        // lignes réellement modifiées, pas celles concernées.
        $query = WatchlistItem::whereIn('id', $this->selectedIds);
        $updated = $query->count();
        $query->update(['priority' => $priority]);
        $this->clearSelection();

        if ($updated > 0) {
            $this->dispatch('toast', message: "Priorité « {$this->priorityLabel($priority)} » appliquée à {$this->filmCount($updated)}.");
        }
    }

    public function bulkRemove(): void
    {
        $removed = WatchlistItem::whereIn('id', $this->selectedIds)->delete();
        $this->clearSelection();
        ComputeCommunityStats::forget();

        if ($removed > 0) {
            $this->dispatch('toast', message: "{$this->filmCount($removed)} retiré".($removed > 1 ? 's' : '').' de votre liste.');
        }
    }

    public function toggleStatusFilter(string $status): void
    {
        abort_unless(WatchStatus::tryFrom($status) !== null, 422);
        $this->statusFilter = $this->toggled($this->statusFilter, $status);
    }

    public function toggleSourceFilter(string $source): void
    {
        abort_unless(WatchSource::tryFrom($source) !== null, 422);
        $this->sourceFilter = $this->toggled($this->sourceFilter, $source);
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilter = [];
    }

    public function clearSourceFilter(): void
    {
        $this->sourceFilter = [];
    }

    public function setSort(string $sort): void
    {
        abort_unless(in_array($sort, ['priority', 'added_desc', 'year_desc', 'rating_desc', 'alpha'], true), 422);
        $this->sortBy = $sort;
    }

    /** Ajoute $value à $list, ou l'en retire si elle y est déjà : les puces de filtre agissent en bascule. */
    private function toggled(array $list, string $value): array
    {
        return in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }

    public function setPriority(int $id, int $priority): void
    {
        abort_unless(in_array($priority, [1, 2, 3], true), 422);

        $item = WatchlistItem::findOrFail($id);
        $changed = (int) $item->priority !== $priority;

        $item->update(['priority' => $priority]);

        if ($changed) {
            $this->dispatch('toast', message: "Priorité « {$this->priorityLabel($priority)} » pour « {$item->title} ».");
        }
    }

    public function remove(int $id): void
    {
        $item = WatchlistItem::findOrFail($id);
        $item->delete();

        $this->dispatch('toast', message: "« {$item->title} » retiré de votre liste.");
    }

    /** Libellé d'une priorité, identique à celui des boutons de la carte (Haute / Moyenne / Basse). */
    private function priorityLabel(int $priority): string
    {
        return [1 => 'haute', 2 => 'moyenne', 3 => 'basse'][$priority];
    }

    /** « 1 film » / « 3 films », pour les messages des actions groupées. */
    private function filmCount(int $count): string
    {
        return $count.' film'.($count > 1 ? 's' : '');
    }

    /**
     * Le filtrage, le tri et les agrégations (compteurs, options de filtre) vivent désormais
     * dans App\Actions\Watchlist\FilterWatchlistItems : ce composant se contente de lui passer
     * son état courant et de transmettre le résultat à la vue.
     */
    public function with(FilterWatchlistItems $filter): array
    {
        return $filter->handle(
            statusFilter: $this->statusFilter,
            sourceFilter: $this->sourceFilter,
            genreFilter: $this->genreFilter,
            directorFilter: $this->directorFilter,
            studioFilter: $this->studioFilter,
            minYear: $this->minYear,
            maxYear: $this->maxYear,
            searchQuery: $this->searchQuery,
            staleOnly: $this->staleOnly,
            sortBy: $this->sortBy,
        );
    }
}
