<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $invite_code = '';

    /**
     * La validation (y compris la politique de mot de passe : 12 caractères,
     * majuscule, minuscule, chiffre, caractère spécial) et la vérification du
     * code d'invitation sont désormais gérées par l'Action Fortify
     * App\Actions\Fortify\CreateNewUser. Les erreurs remontent automatiquement
     * sous forme de ValidationException, que Livewire affiche comme d'habitude.
     */
    public function register(CreatesNewUsers $creator): void
    {
        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'invite_code' => $this->invite_code,
        ]);

        event(new Registered($user));

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirectRoute('verification.notice', navigate: true);
    }
}
