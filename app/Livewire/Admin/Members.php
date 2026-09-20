<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\ComputeMembersOverview;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Members extends Component
{
    /**
     * Supprime définitivement un compte membre. Ses films (`watchlist_items`) partent avec
     * lui via la contrainte `cascadeOnDelete` sur `user_id`. Deux garde-fous : on ne peut pas
     * se supprimer soi-même depuis cette page, ni supprimer le dernier compte administrateur
     * restant (ce qui rendrait l'espace d'administration inaccessible).
     */
    public function deleteMember(int $memberId): void
    {
        $member = User::query()->find($memberId);

        if (! $member) {
            $this->dispatch('toast', message: 'Ce membre n\'existe plus.', type: 'error');

            return;
        }

        if ($member->id === Auth::id()) {
            $this->dispatch('toast', message: 'Impossible de supprimer votre propre compte depuis cette page.', type: 'error');

            return;
        }

        if ($member->isAdmin() && User::query()->where('is_admin', true)->count() <= 1) {
            $this->dispatch('toast', message: 'Impossible de supprimer le dernier compte administrateur.', type: 'error');

            return;
        }

        $memberName = $member->name;
        $member->delete();

        $this->dispatch('toast', message: "Membre « {$memberName} » supprimé.");
    }

    /**
     * Vue d'ensemble : le détail d'un membre (genres, réalisateurs, films…) vit sur sa propre
     * page, voir MemberShow.
     */
    public function with(ComputeMembersOverview $overview): array
    {
        return $overview->handle();
    }
}
