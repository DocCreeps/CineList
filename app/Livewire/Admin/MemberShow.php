<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\ComputeMemberDetail;
use App\Models\User;
use App\Models\WatchlistItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fiche d'un membre (admin) : statistiques détaillées et liste de ses films, en lecture seule.
 * Les notes personnelles du membre ne sont jamais chargées (voir WatchlistItem::ADMIN_SAFE_COLUMNS).
 */
#[Layout('layouts.app')]
class MemberShow extends Component
{
    private const PER_PAGE = 24;

    private const STATUSES = ['to_watch', 'watched', 'to_rewatch'];

    private const SOURCES = ['cinema', 'streaming'];

    private const SORTS = ['added_desc', 'watched_desc', 'title', 'rating_desc', 'year_desc'];

    public int $memberId;

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'source', except: '')]
    public string $source = '';

    #[Url(as: 'genre', except: '')]
    public string $genre = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'tri', except: 'added_desc')]
    public string $sort = 'added_desc';

    /** Nombre de films affichés ; « Afficher plus » l'augmente d'une page. */
    public int $limit = self::PER_PAGE;

    public function mount(User $member): void
    {
        $this->memberId = $member->id;
    }

    /** Un filtre modifié (liste déroulante, recherche) repart de la première page. */
    public function updated(string $name): void
    {
        if ($name !== 'limit') {
            $this->limit = self::PER_PAGE;
        }
    }

    public function setStatus(string $status): void
    {
        $this->status = $this->status === $status ? '' : $status;
        $this->limit = self::PER_PAGE;
    }

    public function setSource(string $source): void
    {
        $this->source = $this->source === $source ? '' : $source;
        $this->limit = self::PER_PAGE;
    }

    public function setGenre(string $genre): void
    {
        $this->genre = $this->genre === $genre ? '' : $genre;
        $this->limit = self::PER_PAGE;
    }

    public function clearFilters(): void
    {
        $this->reset(['status', 'source', 'genre', 'search']);
        $this->limit = self::PER_PAGE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function with(ComputeMemberDetail $computeDetail): array
    {
        $member = User::query()->findOrFail($this->memberId);

        // Ces valeurs viennent aussi de l'URL (?statut=…) : une valeur inconnue est simplement ignorée.
        $status = in_array($this->status, self::STATUSES, true) ? $this->status : '';
        $source = in_array($this->source, self::SOURCES, true) ? $this->source : '';
        $sort = in_array($this->sort, self::SORTS, true) ? $this->sort : 'added_desc';
        $genre = trim($this->genre);
        $search = trim($this->search);

        $all = WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->where('user_id', $member->id)
            ->get(WatchlistItem::ADMIN_SAFE_COLUMNS);

        $detail = $computeDetail->handle($member->id, $all);

        $films = $all
            ->when($status !== '', fn ($items) => $items->where('status', $status))
            ->when($source !== '', fn ($items) => $items->where('source', $source))
            // Un film peut avoir plusieurs genres : on compare à chacun, pas à la chaîne entière.
            ->when($genre !== '', fn ($items) => $items->filter(
                fn ($item) => in_array($genre, array_map('trim', explode(',', (string) $item->genre)), true)
            ))
            ->when($search !== '', fn ($items) => $items->filter(
                fn ($item) => mb_stripos($item->title.' '.$item->director, $search) !== false
            ));

        $films = match ($sort) {
            'watched_desc' => $films->sortByDesc(fn ($item) => $item->watched_at?->timestamp ?? 0),
            'title' => $films->sortBy(fn ($item) => mb_strtolower($item->title)),
            'rating_desc' => $films->sortByDesc(fn ($item) => $item->personal_rating ?? 0),
            'year_desc' => $films->sortByDesc(fn ($item) => $item->year ?? 0),
            default => $films->sortByDesc(fn ($item) => $item->created_at?->timestamp ?? 0),
        };

        $films = $films->values();

        return [
            'member' => $member,
            'detail' => $detail,
            'films' => $films->take($this->limit),
            'filteredCount' => $films->count(),
            'hasFilters' => $status !== '' || $source !== '' || $genre !== '' || $search !== '',
            'activeStatus' => $status,
            'activeSource' => $source,
            'activeGenre' => $genre,
            'activeSort' => $sort,
        ];
    }
}
