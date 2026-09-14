<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\WatchlistItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Members extends Component
{
    /**
     * Id du membre actuellement déplié dans la liste (null = personne). Toggle au clic :
     * cliquer sur le membre déjà ouvert le referme.
     */
    public ?int $selectedMemberId = null;

    public function toggleMember(int $memberId): void
    {
        $this->selectedMemberId = $this->selectedMemberId === $memberId ? null : $memberId;
    }

    /**
     * `WatchlistItem` porte un global scope `owner` qui restreint toute requête aux films de
     * l'utilisateur connecté (voir WatchlistItem::booted). Une vue admin doit au contraire
     * embrasser tous les comptes : chaque requête ci-dessous désactive explicitement ce scope.
     */
    public function with(): array
    {
        $members = User::query()
            ->withCount(['watchlistItems' => fn ($query) => $query->withoutGlobalScope('owner')])
            ->orderBy('created_at')
            ->get();

        $genreCounts = WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->pluck('genre')
            ->flatMap(fn ($genre) => array_map('trim', explode(',', (string) $genre)))
            ->filter()
            ->countBy()
            ->sortDesc();

        return [
            'members' => $members,
            'totalFilms' => $members->sum('watchlist_items_count'),
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
            'selectedMemberDetail' => $this->selectedMemberId ? $this->memberDetail($this->selectedMemberId) : null,
        ];
    }

    /**
     * Détail par catégorie (genre) pour un membre donné, calculé uniquement sur ses films vus
     * ("watched" et "to_rewatch" comptent tous les deux comme déjà visionnés au moins une fois).
     * Calculé à la demande (seulement quand un membre est déplié) plutôt que pour tout le monde
     * d'un coup, pour ne pas alourdir la requête initiale de la page.
     */
    protected function memberDetail(int $memberId): array
    {
        $items = WatchlistItem::query()
            ->withoutGlobalScope('owner')
            ->where('user_id', $memberId)
            ->get();

        $watched = $items->whereIn('status', ['watched', 'to_rewatch']);

        $genreCounts = $watched->pluck('genre')
            ->flatMap(fn ($genre) => array_map('trim', explode(',', (string) $genre)))
            ->filter()
            ->countBy()
            ->sortDesc();

        $rated = $watched->whereNotNull('personal_rating');

        $lastWatched = $watched->sortByDesc('watched_at')->first();

        return [
            'genreCounts' => $genreCounts,
            'topGenreCount' => $genreCounts->first(),
            'watchedCount' => $watched->count(),
            'toWatchCount' => $items->where('status', 'to_watch')->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'lastWatched' => $lastWatched,
        ];
    }
}
