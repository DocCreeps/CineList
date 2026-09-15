<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\ComputeMemberDetail;
use App\Actions\Admin\ComputeMembersOverview;
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
