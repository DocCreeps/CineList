<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Profile extends Component
{
    use ThrottlesWithCountdown;

    public string $name = '';
    public string $email = '';

    /** État du bloc "Zone de danger" : demande de confirmation par mot de passe avant suppression. */
    public bool $confirmingDeletion = false;
    public string $password = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function update(UpdatesUserProfileInformation $updater): void
    {
        $updater->update(Auth::user(), [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        session()->flash('notice', 'Profil mis à jour.');

        $this->email = Auth::user()->fresh()->email;
    }

    public function confirmDeletion(): void
    {
        $this->confirmingDeletion = true;
    }

    public function cancelDeletion(): void
    {
        $this->confirmingDeletion = false;
        $this->password = '';
        $this->resetErrorBag('password');
    }

    /**
     * Suppression définitive et en libre-service du compte : ses films (`watchlist_items`)
     * partent avec lui via `cascadeOnDelete` sur `user_id`, exactement comme pour une suppression
     * par un administrateur (voir Admin\Members::deleteMember). Même garde-fou que là-bas :
     * impossible de fermer le dernier compte administrateur restant, pour ne jamais se retrouver
     * sans accès à l'administration.
     */
    public function deleteAccount(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        $throttleKey = 'delete-account:'.Auth::id();

        if ($this->isThrottled($throttleKey, 5)) {
            return;
        }

        $user = Auth::user();

        if (! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('password', 'Mot de passe incorrect.');

            return;
        }

        if ($user->isAdmin() && User::query()->where('is_admin', true)->count() <= 1) {
            $this->addError('password', 'Impossible de fermer le dernier compte administrateur. Nomme un autre administrateur avant de supprimer celui-ci.');

            return;
        }

        RateLimiter::clear($throttleKey);

        Auth::logout();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        session()->flash('notice', 'Ton compte a bien été supprimé.');

        $this->redirectRoute('login', navigate: true);
    }
}
