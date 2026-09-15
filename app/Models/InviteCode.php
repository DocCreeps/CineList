<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'expires_at', 'max_uses', 'sent_to', 'created_by'])]
class InviteCode extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    /** Administrateur ayant généré ce code (peut être null pour un code plus ancien). */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Compte créé à partir de ce code lors de sa toute première utilisation (historique, usage unique). */
    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /** Historique complet des inscriptions réalisées avec ce code, la plus récente en premier. */
    public function redemptions(): HasMany
    {
        return $this->hasMany(InviteCodeRedemption::class)->latest();
    }

    /** Pas encore expiré, et pas encore épuisé (nombre d'utilisations illimité ou quota non atteint). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses'));
    }

    public function isUnlimited(): bool
    {
        return $this->max_uses === null;
    }

    public function isExhausted(): bool
    {
        return ! $this->isUnlimited() && $this->uses_count >= $this->max_uses;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAvailable(): bool
    {
        return ! $this->isExpired() && ! $this->isExhausted();
    }

    /** Enregistre une inscription réalisée avec ce code : historique + compteur + champs legacy. */
    public function recordUse(User $user): void
    {
        $this->redemptions()->create(['user_id' => $user->id]);

        $this->increment('uses_count');

        // Champs historiques conservés pour compatibilité (affichage "usage unique" simple) :
        // renseignés uniquement lors de la toute première utilisation.
        if (! $this->used_at) {
            $this->update(['used_at' => now(), 'used_by' => $user->id]);
        }
    }
}
