<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'expires_at', 'sent_to', 'created_by'])]
class InviteCode extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** Administrateur ayant généré ce code (peut être null pour un code plus ancien). */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Compte créé à partir de ce code, une fois utilisé. */
    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /** Not used yet, and not expired (or with no expiry at all). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function markUsedBy(User $user): void
    {
        $this->update(['used_at' => now(), 'used_by' => $user->id]);
    }
}
