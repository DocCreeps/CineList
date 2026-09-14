<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'expires_at'])]
class InviteCode extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
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
