<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\ComputeMembersOverview;
use App\Actions\Admin\DeleteMember;
use App\Actions\Stats\ComputeCommunityStats;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Members extends Component
{
    /** Supprime définitivement un compte membre (règles et garde-fous : voir DeleteMember). */
    public function deleteMember(int $memberId, DeleteMember $action): void
    {
        $result = $action->handle($memberId, Auth::user());

        $this->dispatch('toast', message: $result['message'], type: $result['deleted'] ? 'success' : 'error');
    }

    /**
     * Vue d'ensemble : le détail d'un membre (genres, réalisateurs, films…) vit sur sa propre
     * page, voir MemberShow.
     */
    public function with(ComputeMembersOverview $overview, ComputeCommunityStats $communityStats): array
    {
        return $overview->handle($communityStats);
    }
}
