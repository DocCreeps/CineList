<?php

namespace Database\Factories;

use App\Enums\WatchSource;
use App\Enums\WatchStatus;
use App\Models\Movie;
use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchlistItem>
 */
class WatchlistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'movie_id' => Movie::factory(),
            'status' => WatchStatus::ToWatch->value,
            'source' => WatchSource::Streaming->value,
            'priority' => 2,
            'watch_count' => 0,
        ];
    }

    /** Film déjà vu (horodaté et compté une fois). */
    public function watched(?string $at = null): static
    {
        return $this->state(fn () => [
            'status' => WatchStatus::Watched->value,
            'watched_at' => $at ?? now(),
            'watch_count' => 1,
        ]);
    }

    public function toRewatch(?string $at = null): static
    {
        return $this->watched($at)->state(['status' => WatchStatus::ToRewatch->value]);
    }
}
