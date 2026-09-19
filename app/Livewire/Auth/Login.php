<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    use ThrottlesWithCountdown;

    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($this->email).'|'.request()->ip();

        // Le compte à rebours s'affiche côté navigateur (voir ThrottlesWithCountdown).
        if ($this->isThrottled($throttleKey, 5)) {
            return;
        }

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Compte protégé par la double authentification : on ne connecte pas
        // encore l'utilisateur, on le redirige vers l'écran de vérification.
        if (! is_null($user->two_factor_secret) && ! is_null($user->two_factor_confirmed_at)) {
            request()->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            $this->redirectRoute('two-factor.login', navigate: true);

            return;
        }

        Auth::login($user, $this->remember);
        request()->session()->regenerate();

        $this->redirectRoute('home', navigate: true);
    }
}
