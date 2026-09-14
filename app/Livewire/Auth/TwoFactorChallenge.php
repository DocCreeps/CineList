<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class TwoFactorChallenge extends Component
{
    public string $code = '';
    public string $recovery_code = '';
    public bool $usingRecoveryCode = false;

    public function mount(): void
    {
        if (! request()->session()->has('login.id')) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function toggleRecoveryCode(): void
    {
        $this->usingRecoveryCode = ! $this->usingRecoveryCode;
        $this->code = '';
        $this->recovery_code = '';
        $this->resetErrorBag();
    }

    public function authenticate(TwoFactorAuthenticationProvider $provider): void
    {
        $userId = request()->session()->get('login.id');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $throttleKey = 'two-factor:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'code' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        if ($this->usingRecoveryCode) {
            $this->validate(['recovery_code' => ['required', 'string']]);

            $recoveryCodes = collect($user->recoveryCodes());
            $match = $recoveryCodes->first(fn ($stored) => hash_equals($stored, $this->recovery_code));

            if (! $match) {
                RateLimiter::hit($throttleKey, 60);
                $this->addError('recovery_code', 'Code de récupération invalide.');

                return;
            }

            $user->replaceRecoveryCode($match);
        } else {
            $this->validate(['code' => ['required', 'string']]);

            $valid = $user->two_factor_secret
                && $provider->verify(decrypt($user->two_factor_secret), $this->code);

            if (! $valid) {
                RateLimiter::hit($throttleKey, 60);
                $this->addError('code', 'Code invalide.');

                return;
            }
        }

        RateLimiter::clear($throttleKey);

        $remember = request()->session()->pull('login.remember', false);
        request()->session()->forget('login.id');

        Auth::login($user, $remember);
        request()->session()->regenerate();

        $this->redirectRoute('home', navigate: true);
    }
}
