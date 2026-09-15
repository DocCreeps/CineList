<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\ComputeMemberDetail;
use App\Actions\Admin\ComputeMembersOverview;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
     * Supprime définitivement un compte membre. Ses films (`watchlist_items`) partent avec
     * lui via la contrainte `cascadeOnDelete` sur `user_id`. Deux garde-fous : on ne peut pas
     * se supprimer soi-même depuis cette page, ni supprimer le dernier compte administrateur
     * restant (ce qui rendrait l'espace d'administration inaccessible).
     */
    public function deleteMember(int $memberId): void
    {
        $member = User::query()->find($memberId);

        if (! $member) {
            return;
        }

        if ($member->id === Auth::id()) {
            session()->flash('notice', 'Impossible de supprimer votre propre compte depuis cette page.');

            return;
        }

        if ($member->isAdmin() && User::query()->where('is_admin', true)->count() <= 1) {
            session()->flash('notice', 'Impossible de supprimer le dernier compte administrateur.');

            return;
        }

        $memberName = $member->name;
        $member->delete();

        if ($this->selectedMemberId === $memberId) {
            $this->selectedMemberId = null;
        }

        session()->flash('notice', "Membre « {$memberName} » supprimé.");
    }

    /**
     * Calculé à la demande (seulement quand un membre est déplié) plutôt que pour tout le monde
     * d'un coup, pour ne pas alourdir la requête initiale de la page.
     */
    public function with(ComputeMembersOverview $overview, ComputeMemberDetail $detail): array
    {
        return [
            ...$overview->handle(),
            'selectedMemberDetail' => $this->selectedMemberId ? $detail->handle($this->selectedMemberId) : null,
        ];
    }
}
