<?php

namespace App\Livewire\Admin;

use App\Models\DemoLink;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DemoLinks extends Component
{
    public string $label = '';
    public string $expiresInDays = '';
    public ?string $lastGeneratedUrl = null;

    /** @return array<int, DemoLink> */
    public function getLinksProperty(): array
    {
        return DemoLink::query()->with('creator')->latest()->limit(100)->get()->all();
    }

    public function getDemoUserExistsProperty(): bool
    {
        return User::query()->demoTemplate()->exists();
    }

    public function getActiveSandboxesProperty(): int
    {
        return User::query()->demoSandboxes()->where('demo_expires_at', '>', now())->count();
    }

    public function generate(): void
    {
        $this->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'expiresInDays' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if (! $this->demoUserExists) {
            $this->addError('label', "Aucun compte modèle : lancez d'abord `php artisan demo:create`.");

            return;
        }

        $throttleKey = 'demo-link-generate:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->addError('label', 'Trop de générations en peu de temps, réessaie dans une minute.');

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $link = DemoLink::create([
            'token' => Str::random(40),
            'label' => $this->label !== '' ? $this->label : null,
            'expires_at' => $this->expiresInDays !== '' ? now()->addDays((int) $this->expiresInDays) : null,
            'created_by' => Auth::id(),
        ]);

        $this->lastGeneratedUrl = $link->url();
        $this->dispatch('toast', message: 'Lien démo créé.');
        $this->reset(['label', 'expiresInDays']);
    }

    /** Désactive le lien tout en gardant son historique (compteur, dernière utilisation). */
    public function revoke(int $linkId): void
    {
        $link = DemoLink::query()->whereNull('revoked_at')->find($linkId);

        if (! $link) {
            $this->dispatch('toast', message: 'Ce lien est introuvable ou déjà révoqué.', type: 'error');

            return;
        }

        $link->update(['revoked_at' => now()]);

        $this->dispatch('toast', message: 'Lien révoqué : il ne permet plus de se connecter.');
    }

    /** Suppression définitive d'un lien déjà révoqué ou expiré. */
    public function delete(int $linkId): void
    {
        $link = DemoLink::query()->find($linkId);

        if (! $link || $link->isActive()) {
            $this->dispatch('toast', message: 'Révoquez le lien avant de le supprimer.', type: 'error');

            return;
        }

        $link->delete();

        $this->dispatch('toast', message: 'Lien supprimé.');
    }
}
