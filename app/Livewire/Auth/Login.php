<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ThrottlesWithCountdown;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    /** Échecs de connexion tolérés par adresse IP (tous e-mails confondus) sur la fenêtre ci-dessous. */
    private const IP_MAX_ATTEMPTS = 20;

    private const IP_DECAY_SECONDS = 300;

    public function login(): void
    {
        // Normalisé AVANT la validation : un espace collé avant ou après l'adresse ne doit pas la rendre invalide.
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Deux limites cumulées : 5 essais par minute pour un couple e-mail + IP (force brute sur un
        // compte), et un plafond par IP seule (une IP qui essaie beaucoup d'e-mails différents, par
        // exemple avec une liste d'identifiants volés, passe sous la première limite à chaque fois).
        $throttleKey = $this->email.'|'.request()->ip();
        $ipKey = 'login-ip|'.request()->ip();

        // Le compte à rebours s'affiche côté navigateur (voir ThrottlesWithCountdown).
        if ($this->isThrottled($throttleKey, 5) || $this->isThrottled($ipKey, self::IP_MAX_ATTEMPTS)) {
            return;
        }

        $user = User::where('email', $this->email)->first();

        // Le compte démo n'a pas de mot de passe utilisable : il passe uniquement par /demo.
        if (! $user || $user->isDemo()) {
            // Vérification factice : sans elle, un e-mail inconnu (ou un compte démo) répond bien plus vite
            // qu'un e-mail connu (pas de calcul de hachage), ce qui permet de deviner quels comptes existent.
            Hash::check($this->password, $this->dummyHash());

            $this->recordFailure($throttleKey, $ipKey);
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        // Même message que pour un e-mail inconnu : l'écran ne doit pas révéler quelles adresses ont un compte.
        if (! Hash::check($this->password, $user->password)) {
            $this->recordFailure($throttleKey, $ipKey);
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        // Seul le compteur du couple e-mail + IP est remis à zéro : celui de l'IP seule continue de
        // décroître tout seul, sinon un compte valide suffirait à le réinitialiser entre deux essais.
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

    private function recordFailure(string $throttleKey, string $ipKey): void
    {
        RateLimiter::hit($throttleKey, 60);
        RateLimiter::hit($ipKey, self::IP_DECAY_SECONDS);
    }

    /**
     * Hachage jamais associé à un compte, calculé avec la configuration de hachage en cours (donc
     * le même coût qu'un vrai mot de passe) puis gardé en cache. La clé inclut le coût bcrypt pour
     * que le hachage soit recalculé si BCRYPT_ROUNDS change.
     */
    private function dummyHash(): string
    {
        return Cache::rememberForever(
            'auth.dummy-hash.'.config('hashing.bcrypt.rounds', 12),
            fn () => Hash::make(Str::random(32)),
        );
    }
}
