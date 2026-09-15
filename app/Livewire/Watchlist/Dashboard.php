<?php

namespace App\Livewire\Watchlist;

use App\Actions\Watchlist\FilterWatchlistItems;
use App\Actions\Watchlist\UpdateWatchlistItemStatus;
use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use Livewire\Component;

class Dashboard extends Component
{
    use InteractsWithMovies;

    /** @var array<int, string> Empty means "no filter" (all statuses). */
    public array $statusFilter = [];

    /** @var array<int, string> Empty means "no filter" (all sources). */
    public array $sourceFilter = [];

    /** Single-value filters (open-ended sets of values, so a dropdown rather than chips). */
    public string $genreFilter = '';
    public string $directorFilter = '';
    public string $studioFilter = '';

    public string $sortBy = 'priority';

    /** Whether the "already watched" section is expanded (collapsed/hidden by default). */
    public bool $showWatched = false;

    /** Optional release-year bounds, same idea as the search page's minYear. */
    public ?int $minYear = null;
    public ?int $maxYear = null;

    /** Matches title or personal note (case-insensitive substring). */
    public string $searchQuery = '';

    /** When true, restricts the grid to "to watch" films added more than 3 months ago. */
    public bool $staleOnly = false;

    /** @var array<int, int> IDs currently checked in the grid, for the bulk-action toolbar. */
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
     * Adds every currently-displayed film to the selection (called with the visible IDs from
     * the view). Acts as a toggle: if every visible film is already selected, it deselects
     * them instead, so the button can also be used to clear the current view's selection.
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

    /** Applies the same watched-date logic as setStatus() to every selected film. */
    public function bulkSetStatus(string $status, UpdateWatchlistItemStatus $action): void
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);

        foreach ($this->selectedIds as $id) {
            $action->handle(WatchlistItem::findOrFail($id), $status);
        }

        $this->clearSelection();
    }

    public function bulkSetPriority(int $priority): void
    {
        abort_unless(in_array($priority, [1, 2, 3], true), 422);
        WatchlistItem::whereIn('id', $this->selectedIds)->update(['priority' => $priority]);
        $this->clearSelection();
    }

    public function bulkRemove(): void
    {
        WatchlistItem::whereIn('id', $this->selectedIds)->delete();
        $this->clearSelection();
    }

    public function toggleStatusFilter(string $status): void
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);
        $this->statusFilter = $this->toggled($this->statusFilter, $status);
    }

    public function toggleSourceFilter(string $source): void
    {
        abort_unless(in_array($source, ['cinema', 'streaming'], true), 422);
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

    /** Adds $value to $list, or removes it if already present, so filter chips act as toggles. */
    private function toggled(array $list, string $value): array
    {
        return in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }

    public function setStatus(int $id, string $status, UpdateWatchlistItemStatus $action): void
    {
        $action->handle(WatchlistItem::findOrFail($id), $status);
    }

    public function setPriority(int $id, int $priority): void
    {
        abort_unless(in_array($priority, [1, 2, 3], true), 422);
        WatchlistItem::findOrFail($id)->update(['priority' => $priority]);
    }

    public function remove(int $id): void
    {
        WatchlistItem::findOrFail($id)->delete();
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
