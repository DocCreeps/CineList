<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Attributes\Layout;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

#[Layout('layouts.guest')]
class Register extends Component
{
    use ThrottlesWithCountdown;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $invite_code = '';

    /**
     * Validation et vérification du code d'invitation déléguées à
     * App\Actions\Fortify\CreateNewUser. Limite manuelle par IP en plus (10/min) : les
     * composants Livewire ne passent pas par le routeur HTTP, donc pas de throttle de route.
     *
     * Seules les tentatives qui atteignent la validation métier (code d'invitation invalide,
     * e-mail déjà pris…) consomment le quota : une simple faute de saisie (champ manquant,
     * mot de passe trop court…) ne bloque pas les autres visiteurs situés derrière la même
     * IP (NAT partagé). Le quota protège toujours du brute-force des codes d'invitation,
     * puisque chaque code erroné déclenche une ValidationException.
     */
    public function register(CreatesNewUsers $creator): void
    {
        $throttleKey = 'register:'.request()->ip();

        if ($this->isThrottled($throttleKey, 10)) {
            return;
        }

        try {
            $user = $creator->create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'invite_code' => $this->invite_code,
            ]);
        } catch (ValidationException $exception) {
            RateLimiter::hit($throttleKey, 60);

            throw $exception;
        }

        // Inscription réussie : rien à compter, on réinitialise au cas où.
        RateLimiter::clear($throttleKey);

        event(new Registered($user));

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirectRoute('verification.notice', navigate: true);
    }
}
