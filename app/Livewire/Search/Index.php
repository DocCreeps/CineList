<?php

namespace App\Livewire\Search;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use Livewire\Component;

class Index extends Component
{
    use InteractsWithMovies;

    public string $queryTitle = '';
    public string $queryDirector = '';
    public string $queryActor = '';
    public string $queryStudio = '';

    /** Champ réellement envoyé à TMDB ; les autres champs remplis affinent les résultats en local. */
    public string $searchMode = 'title';

    public ?int $minYear = null;

    public bool $hasSearched = false;
    public ?string $searchError = null;

    public string $roleFilter = 'all';
    public int $page = 1;
    private const PER_PAGE = 18;

    public function updatedQueryTitle(): void
    {
        $this->refreshSearch();
    }

    public function updatedQueryDirector(): void
    {
        $this->refreshSearch();
    }

    public function updatedQueryActor(): void
    {
        $this->refreshSearch();
    }

    public function updatedQueryStudio(): void
    {
        $this->refreshSearch();
    }

    public function updatedMinYear(): void
    {
        if ($this->hasSearched) {
            $this->performSearch();
        }
    }

    /**
     * Les quatre champs peuvent être remplis en même temps. L'un d'eux (par priorité : titre >
     * réalisateur > acteur > studio) pilote la requête TMDB ; les autres champs remplis sont
     * appliqués ensuite comme affinages locaux dans filteredResults(). Réévalué à chaque frappe,
     * pour que le champ qui pilote la requête puisse changer au fil de la saisie.
     */
    private function refreshSearch(): void
    {
        $this->roleFilter = 'all';
        $mode = $this->primaryMode();

        if ($mode === null) {
            $this->results = [];
            $this->hasSearched = false;
            $this->searchError = null;
            $this->searchMode = 'title';
            $this->page = 1;
            return;
        }

        $this->searchMode = $mode;
        $this->performSearch();
    }

    private function primaryMode(): ?string
    {
        return match (true) {
            mb_strlen(trim($this->queryTitle)) >= 2 => 'title',
            mb_strlen(trim($this->queryDirector)) >= 2 => 'director',
            mb_strlen(trim($this->queryActor)) >= 2 => 'actor',
            mb_strlen(trim($this->queryStudio)) >= 2 => 'studio',
            default => null,
        };
    }

    public function currentQuery(): string
    {
        return match ($this->searchMode) {
            'director' => $this->queryDirector,
            'actor' => $this->queryActor,
            'studio' => $this->queryStudio,
            default => $this->queryTitle,
        };
    }

    /** Les champs non vides (2 caractères ou plus), pour l'affichage et pour piloter les filtres combinés. */
    public function activeQueries(): array
    {
        $entries = [];
        if (mb_strlen(trim($this->queryTitle)) >= 2) $entries['title'] = ['Titre', $this->queryTitle];
        if (mb_strlen(trim($this->queryDirector)) >= 2) $entries['director'] = ['Réalisateur', $this->queryDirector];
        if (mb_strlen(trim($this->queryActor)) >= 2) $entries['actor'] = ['Acteur', $this->queryActor];
        if (mb_strlen(trim($this->queryStudio)) >= 2) $entries['studio'] = ['Studio', $this->queryStudio];

        return $entries;
    }

    private function performSearch(): void
    {
        $query = $this->currentQuery();
        $response = app(TmdbClient::class)->searchFilms($query, $this->searchMode, $this->minYear);

        $this->results = $response['results'];
        $this->searchError = $response['error'];
        $this->hasSearched = true;
        $this->page = 1;
    }

    public function setRoleFilter(string $filter): void
    {
        abort_unless(in_array($filter, ['all', 'acting', 'voice'], true), 422);
        $this->roleFilter = $filter;
        $this->page = 1;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Applique en filtres locaux supplémentaires les champs qui n'ont pas servi de requête TMDB
     * principale (sous-chaîne sur titre/réalisateur, ou distinction rôle joué/doublage pour un
     * acteur), afin que les résultats satisfassent tous les champs remplis à la fois.
     */
    private function filteredResults(): array
    {
        $results = collect($this->results);

        if ($this->searchMode !== 'title' && mb_strlen(trim($this->queryTitle)) >= 2) {
            $needle = mb_strtolower(trim($this->queryTitle));
            $results = $results->filter(fn($m) => str_contains(mb_strtolower($m['title'] ?? ''), $needle));
        }

        if ($this->searchMode !== 'director' && mb_strlen(trim($this->queryDirector)) >= 2) {
            $needle = mb_strtolower(trim($this->queryDirector));
            $results = $results->filter(fn($m) => str_contains(mb_strtolower($m['director'] ?? ''), $needle));
        }

        if ($this->searchMode !== 'actor' && mb_strlen(trim($this->queryActor)) >= 2) {
            $needle = mb_strtolower(trim($this->queryActor));
            $results = $results->filter(fn($m) => str_contains(mb_strtolower($m['actors'] ?? ''), $needle));
        }

        if ($this->searchMode !== 'studio' && mb_strlen(trim($this->queryStudio)) >= 2) {
            $needle = mb_strtolower(trim($this->queryStudio));
            $results = $results->filter(fn($m) => str_contains(mb_strtolower($m['studio'] ?? ''), $needle));
        }

        if ($this->searchMode === 'actor' && $this->roleFilter !== 'all') {
            $wantVoice = $this->roleFilter === 'voice';
            $results = $results->filter(fn($m) => (bool) ($m['is_voice'] ?? false) === $wantVoice);
        }

        return $results->values()->all();
    }

    public function clearSearch(): void
    {
        $this->queryTitle = '';
        $this->queryDirector = '';
        $this->queryActor = '';
        $this->queryStudio = '';
        $this->results = [];
        $this->hasSearched = false;
        $this->searchError = null;
        $this->roleFilter = 'all';
        $this->page = 1;
    }

    public function with(): array
    {
        $filtered = $this->filteredResults();
        $totalPages = max(1, (int) ceil(count($filtered) / self::PER_PAGE));
        $this->page = min($this->page, $totalPages);
        $pageResults = array_slice($filtered, ($this->page - 1) * self::PER_PAGE, self::PER_PAGE);

        $roleCounts = null;
        if ($this->searchMode === 'actor') {
            $roleCounts = [
                'acting' => collect($this->results)->filter(fn($m) => empty($m['is_voice']))->count(),
                'voice' => collect($this->results)->filter(fn($m) => !empty($m['is_voice']))->count(),
            ];
        }

        // Films de cette page déjà présents dans la watchlist (quel que soit le statut), pour que
        // la vue puisse griser la carte et remplacer les boutons d'ajout par un badge de statut.
        $inList = WatchlistItem::whereIn('tmdb_id', collect($pageResults)->pluck('tmdb_id'))
            ->pluck('status', 'tmdb_id');

        return [
            'pageResults' => $pageResults,
            'resultsTotal' => count($filtered),
            'totalPages' => $totalPages,
            'roleCounts' => $roleCounts,
            'inList' => $inList,
        ];
    }
}
