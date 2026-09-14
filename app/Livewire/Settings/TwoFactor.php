<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TwoFactor extends Component
{
    public string $code = '';
    public bool $showingQrCode = false;
    public bool $showingRecoveryCodes = false;

    public function getEnabledProperty(): bool
    {
        return ! is_null(Auth::user()->two_factor_secret);
    }

    public function getConfirmedProperty(): bool
    {
        return ! is_null(Auth::user()->two_factor_confirmed_at);
    }

    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        $enable(Auth::user());

        $this->showingQrCode = true;
    }

    public function confirm(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->validate(['code' => ['required', 'string']]);

        try {
            $confirm(Auth::user(), $this->code);
        } catch (\Exception) {
            $this->addError('code', 'Code invalide.');

            return;
        }

        $this->code = '';
        $this->showingQrCode = false;
        $this->showingRecoveryCodes = true;

        session()->flash('notice', 'Double authentification activée.');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $generate(Auth::user());

        $this->showingRecoveryCodes = true;
    }

    public function disable(DisableTwoFactorAuthentication $disable): void
    {
        $disable(Auth::user());

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;

        session()->flash('notice', 'Double authentification désactivée.');
    }
}
