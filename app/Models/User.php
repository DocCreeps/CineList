<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\StatsCache;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
// 'is_admin', 'is_demo' et 'demo_expires_at' volontairement absents : jamais assignable via un payload utilisateur, seulement
// en base ou via `php artisan user:make-admin`.
#[Hidden(['password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable, TwoFactorAuthenticatable;

    /**
     * Attributs à caster.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_demo' => 'boolean',
            'demo_expires_at' => 'datetime',
        ];
    }

    /** @return HasMany<WatchlistItem, $this> */
    /**
     * L'adresse e-mail est normalisée à l'écriture (minuscules, sans espaces autour) : SQLite et la
     * règle `unique` de Laravel distinguent les majuscules, ce qui permettrait sinon d'ouvrir deux
     * comptes « Jean@x.fr » et « jean@x.fr » avec des limites de connexion différentes.
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => $value === null ? null : Str::lower(trim($value)));
    }

    protected static function booted(): void
    {
        // Supprimer un compte supprime ses films (cascade en base, sans événement Eloquent) : les
        // stats mises en cache ne sont plus justes. Un compte créé ou modifié ne change rien : la
        // liste des membres est lue en direct, et un nouveau membre n'a encore aucun film.
        static::deleted(fn () => StatsCache::forget());
    }

    public function watchlistItems(): HasMany
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /** Compte fictif de démonstration : le modèle ou l'un des bacs à sable créés pour les visiteurs. */
    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    /** Compte modèle de la démo : jamais utilisé directement, il est copié pour chaque visiteur. */
    public function isDemoTemplate(): bool
    {
        return $this->isDemo() && $this->demo_expires_at === null;
    }

    /** Vrais membres uniquement (exclut le modèle et tous les bacs à sable de la démo). */
    public function scopeRealMembers(Builder $query): void
    {
        $query->where('is_demo', false);
    }

    public function scopeDemoTemplate(Builder $query): void
    {
        $query->where('is_demo', true)->whereNull('demo_expires_at');
    }

    public function scopeDemoSandboxes(Builder $query): void
    {
        $query->where('is_demo', true)->whereNotNull('demo_expires_at');
    }
}
