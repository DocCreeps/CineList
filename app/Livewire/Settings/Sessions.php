<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Sessions extends Component
{
    use ThrottlesWithCountdown;

    public string $password = '';
    public bool $confirmingLogout = false;

    /** @return array<int, object> */
    public function getSessionsProperty(): array
    {
        return DB::table('sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) {
                $agent = $this->parseUserAgent($session->user_agent);

                return (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'is_current_device' => $session->id === request()->session()->getId(),
                    'last_active' => \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'browser' => $agent['browser'],
                    'platform' => $agent['platform'],
                ];
            })
            ->all();
    }

    public function confirmLogout(): void
    {
        $this->confirmingLogout = true;
    }

    public function logoutOtherDevices(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        $throttleKey = 'logout-other-devices:'.Auth::id();

        if ($this->isThrottled($throttleKey, 5)) {
            return;
        }

        if (! Hash::check($this->password, Auth::user()->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('password', 'Mot de passe incorrect.');

            return;
        }

        RateLimiter::clear($throttleKey);

        // Change l'empreinte du mot de passe : le middleware AuthenticateSession (groupe `web`) invalide
        // alors toute autre session de ce compte, quel que soit le pilote de session.
        Auth::logoutOtherDevices($this->password);

        // Avec le pilote « database », on supprime en plus les lignes de la table `sessions` : elles
        // disparaissent tout de suite de la liste au lieu d'attendre le prochain passage de l'autre appareil.
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', Auth::id())
                ->where('id', '!=', request()->session()->getId())
                ->delete();
        }

        $this->password = '';
        $this->confirmingLogout = false;

        $this->dispatch('toast', message: 'Déconnecté des autres appareils.');
    }

    /** @return array{browser: string, platform: string} */
    protected function parseUserAgent(?string $userAgent): array
    {
        $userAgent ??= '';

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome') => 'Safari',
            default => 'Navigateur inconnu',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Plateforme inconnue',
        };

        return ['browser' => $browser, 'platform' => $platform];
    }
}
