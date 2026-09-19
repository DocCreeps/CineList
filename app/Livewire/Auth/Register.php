<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Attributes\Layout;
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
     */
    public function register(CreatesNewUsers $creator): void
    {
        $throttleKey = 'register:'.request()->ip();

        if ($this->isThrottled($throttleKey, 10)) {
            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'invite_code' => $this->invite_code,
        ]);

        RateLimiter::clear($throttleKey);

        event(new Registered($user));

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirectRoute('verification.notice', navigate: true);
    }
}
