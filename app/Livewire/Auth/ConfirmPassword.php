<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ConfirmPassword extends Component
{
    use ThrottlesWithCountdown;

    public string $password = '';

    public function confirm(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        // Un poste déverrouillé ou une session volée ne doit pas permettre de bruteforcer le
        // mot de passe pour accéder aux pages sensibles (réglages, administration).
        $throttleKey = 'confirm-password:'.Auth::id();

        if ($this->isThrottled($throttleKey, 5)) {
            return;
        }

        if (! Hash::check($this->password, Auth::user()->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('password', 'Mot de passe incorrect.');

            return;
        }

        RateLimiter::clear($throttleKey);

        request()->session()->put('auth.password_confirmed_at', time());

        $this->redirect(
            redirect()->intended(route('settings.profile'))->getTargetUrl(),
            navigate: true
        );
    }
}
