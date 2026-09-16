<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ConfirmPassword extends Component
{
    public string $password = '';

    public function confirm(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        // Un poste déverrouillé ou une session volée ne doit pas permettre de bruteforcer le
        // mot de passe pour accéder aux pages sensibles (réglages, administration).
        $throttleKey = 'confirm-password:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'password' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
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
